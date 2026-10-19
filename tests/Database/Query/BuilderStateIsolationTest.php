<?php

namespace Bow\Tests\Database\Query;

use Bow\Database\Barry\Model;
use Bow\Database\Collection;
use Bow\Database\Database;
use Bow\Database\Pagination;
use Bow\Tests\Config\TestingConfiguration;
use Bow\Tests\Database\Stubs\BuilderOtherRowModelStub as OtherRow;
use Bow\Tests\Database\Stubs\BuilderProbeRowModelStub as ProbeRow;
use Throwable;

/**
 * Model::query() used to memoize ONE process-wide builder (Model::$builder)
 * and hand it back for every query on the same model class, and QueryBuilder
 * only reset its state on the success path. Whatever a failed statement, or a
 * chain that threw while being built, left behind (bindings, where clause,
 * the first() flag, order, locks, eager loads) was replayed into the next
 * query on that model in the same process: a queue worker, a bus consumer, a
 * cron command.
 *
 * Runs on in-memory SQLite only, so it needs no external service.
 */
class BuilderStateIsolationTest extends \PHPUnit\Framework\TestCase
{
    public static function setUpBeforeClass(): void
    {
        Database::configure(TestingConfiguration::getConfig()['database']);
    }

    public static function tearDownAfterClass(): void
    {
        (new \ReflectionProperty(Model::class, 'builder'))->setValue(null, null);
    }

    protected function setUp(): void
    {
        // The slot is static and survives from one test to the next exactly
        // as it survives from one job to the next in a worker: start each
        // test from an empty slot so a test only sees its own leak.
        (new \ReflectionProperty(Model::class, 'builder'))->setValue(null, null);

        Database::connection('sqlite');

        Database::statement('DROP TABLE IF EXISTS builder_probe_rows');
        Database::statement(
            'CREATE TABLE builder_probe_rows (id INTEGER PRIMARY KEY, organization_id TEXT NOT NULL,'
            . " status TEXT NOT NULL CHECK (status IN ('trial','active','processing','initialized','suspended')),"
            . ' external_id TEXT, deleted_at TEXT, hits INTEGER NOT NULL DEFAULT 0)'
        );
        Database::statement('DROP TABLE IF EXISTS builder_other_rows');
        Database::statement('CREATE TABLE builder_other_rows (id INTEGER PRIMARY KEY, name TEXT)');

        Database::table('builder_probe_rows')->insert([
            ['id' => 1, 'organization_id' => 'org-a', 'status' => 'processing'],
            ['id' => 2, 'organization_id' => 'org-a', 'status' => 'active'],
            ['id' => 3, 'organization_id' => 'org-b', 'status' => 'active'],
            ['id' => 4, 'organization_id' => 'org-b', 'status' => 'trial'],
        ]);
        Database::table('builder_other_rows')->insert([['id' => 1, 'name' => 'other']]);
    }

    private static function statusOf(int $id): string
    {
        return Database::select('SELECT status FROM builder_probe_rows WHERE id = ?', [$id])[0]->status;
    }

    public function test_every_query_gets_its_own_builder()
    {
        $this->assertNotSame(ProbeRow::query(), ProbeRow::query());
    }

    /**
     * A job's outcome write fails (here a CHECK violation), its catch block
     * then releases the claim on the same model. The failed UPDATE used to
     * leave its bindings in the shared builder, so the recovery UPDATE died
     * with HY093 (wrong parameter count).
     */
    public function test_a_failed_update_does_not_break_the_recovery_query_on_the_same_model()
    {
        $row = ProbeRow::where('id', 1)->first();
        $row->status = 'bogus';

        try {
            $row->persist();
            $this->fail('the CHECK constraint should reject the update');
        } catch (Throwable) {
            // the job's catch block
        }

        $released = ProbeRow::where('id', 1)
            ->where('status', 'processing')
            ->update(['status' => 'initialized']);

        $this->assertSame(1, $released);
        $this->assertSame('initialized', self::statusOf(1));
    }

    /**
     * The failure was sticky: every later query on the model failed too,
     * until a query on ANOTHER model class swapped the single static slot.
     */
    public function test_the_model_recovers_after_a_failed_update()
    {
        $row = ProbeRow::where('id', 1)->first();
        $row->status = 'bogus';

        try {
            $row->persist();
        } catch (Throwable) {
        }

        $failures = 0;

        foreach ([1, 2, 3] as $id) {
            try {
                ProbeRow::where('id', $id)->first();
            } catch (Throwable) {
                $failures++;
            }
        }

        $this->assertSame(0, $failures, 'the next three queries on this model all failed');
    }

    /**
     * A chain that throws while it is being BUILT (where() with a null value
     * throws) left `organization_id = ?` bound to org A in the builder. The
     * next query, for org B, was silently ANDed with it.
     */
    public function test_a_chain_that_throws_while_building_does_not_narrow_the_next_query()
    {
        try {
            ProbeRow::where('organization_id', 'org-a')->where('external_id', null)->first();
            $this->fail('where() with a null value should throw');
        } catch (Throwable) {
        }

        $rows = ProbeRow::where('organization_id', 'org-b')->get();
        $this->assertCount(2, $rows, "org B rows went missing: the query inherited org A's where");

        try {
            ProbeRow::where('organization_id', 'org-a')->where('external_id', null)->first();
        } catch (Throwable) {
        }

        $updated = ProbeRow::where('id', 3)->update(['status' => 'suspended']);
        $this->assertSame(1, $updated, "the update on org B's row silently hit 0 rows");
    }

    /**
     * Same leak with an OR in the stale clause: the next UPDATE scoped to
     * org B became `organization_id = 'org-a' or status = 'trial' and
     * organization_id = 'org-b'` and rewrote org A's rows.
     */
    public function test_a_stale_or_clause_does_not_widen_an_update_to_another_scope()
    {
        try {
            ProbeRow::where('organization_id', 'org-a')
                ->orWhere('status', 'trial')
                ->orderBy('created at') // unsafe identifier: throws before execution
                ->get();
            $this->fail('orderBy() should reject the identifier');
        } catch (Throwable) {
        }

        ProbeRow::where('organization_id', 'org-b')->update(['status' => 'suspended']);

        $this->assertSame('processing', self::statusOf(1), 'org A row 1 was rewritten by an org B update');
        $this->assertSame('active', self::statusOf(2), 'org A row 2 was rewritten by an org B update');
    }

    /**
     * A failed first() left `first = true`: the next get() returned ONE model
     * instead of a Collection, and a foreach over it ran zero times.
     */
    public function test_a_failed_first_does_not_turn_the_next_get_into_a_single_row()
    {
        try {
            ProbeRow::whereNull('no_such_column')->first();
            $this->fail('the unknown column should fail');
        } catch (Throwable) {
        }

        $rows = ProbeRow::whereNull('deleted_at')->get();

        $this->assertInstanceOf(Collection::class, $rows);
        $this->assertCount(4, $rows);
    }

    /**
     * Defence in depth on a builder that IS reused on purpose (a local
     * variable): a failed aggregate leaves nothing behind.
     */
    public function test_a_failed_count_leaves_nothing_on_a_reused_builder()
    {
        $query = Database::table('builder_probe_rows');

        try {
            $query->where('organization_id', 'org-a')->count('no_such_column');
            $this->fail('the unknown column should fail');
        } catch (Throwable) {
        }

        $this->assertCount(2, $query->where('organization_id', 'org-b')->get());
    }

    /**
     * An aggregate is a terminal statement: the order and row lock it does
     * not use no longer survive it into the next select on the builder.
     */
    public function test_a_count_leaves_no_order_or_lock_behind()
    {
        $query = Database::table('builder_probe_rows');

        $this->assertSame(4, (int) $query->orderBy('id', 'desc')->lockForUpdate()->count());

        // SQLite has no FOR UPDATE: a leaked lock flag makes this a syntax error.
        $rows = $query->get();

        $this->assertSame(1, (int) $rows[0]->id, 'the order of the count leaked into the next select');
    }

    /**
     * increment()/decrement() never cleared their bindings, even on success:
     * the second increment on the same builder carried the first one's id.
     */
    public function test_increment_does_not_carry_its_bindings_into_the_next_statement()
    {
        $query = Database::table('builder_probe_rows');

        try {
            $query->where('id', 2)->increment('hits');
            $query->where('id', 3)->increment('hits');
        } catch (Throwable) {
        }

        $hits = Database::select('SELECT id, hits FROM builder_probe_rows ORDER BY id');

        $this->assertSame([0, 1, 1, 0], array_map(fn ($row) => (int) $row->hits, $hits));
    }

    /**
     * A failed eager-loaded get() left its relation list on the builder: the
     * next get() on it tried to load a relation nobody asked for.
     */
    public function test_a_failed_eager_load_does_not_reach_the_next_get()
    {
        $query = ProbeRow::query()->eager('noSuchRelation');

        try {
            $query->whereNull('no_such_column')->get();
            $this->fail('the unknown column should fail');
        } catch (Throwable) {
        }

        $this->assertCount(4, $query->get());
    }

    /**
     * retrieve()/retrieveBy() used to build their query in separate calls on
     * the shared builder; they now chain on one builder.
     */
    public function test_retrieve_and_retrieve_by_still_work()
    {
        $one = ProbeRow::retrieve(3);
        $this->assertInstanceOf(ProbeRow::class, $one);
        $this->assertSame('org-b', $one->organization_id);

        $narrow = ProbeRow::retrieve(2, ['id', 'status']);
        $this->assertSame(['id' => 2, 'status' => 'active'], $narrow->toArray());

        $many = ProbeRow::retrieve([1, 4]);
        $this->assertInstanceOf(Collection::class, $many);
        $this->assertCount(2, $many);

        $this->assertNull(ProbeRow::retrieve(99));

        $this->assertCount(2, ProbeRow::retrieveBy('organization_id', 'org-b'));
    }

    /**
     * paginate() and last() run a count() next to their select and keep what
     * that select needs across it.
     */
    public function test_paginate_and_last_still_work()
    {
        $page = ProbeRow::where('organization_id', 'org-a')->paginate(1, 2);

        $this->assertInstanceOf(Pagination::class, $page);
        $this->assertSame(2, $page->total());
        $this->assertCount(1, $page->items());
        $this->assertSame(2, (int) $page->items()->first()->id);

        $this->assertSame(2, (int) ProbeRow::where('organization_id', 'org-a')->last()->id);
        $this->assertSame(
            3,
            (int) ProbeRow::where('organization_id', 'org-b')->orderBy('id', 'desc')->last()->id,
            'last() lost its order by'
        );
    }

    /**
     * persist() on a new row reads the insert id from the builder it inserted
     * with, whichever model ran a query last.
     */
    public function test_persist_reads_the_insert_id_from_its_own_builder()
    {
        $row = ProbeRow::create(['organization_id' => 'org-c', 'status' => 'trial']);

        OtherRow::query(); // the last query() of ANY model now points elsewhere

        $this->assertSame(1, $row->persist());
        $this->assertSame(5, (int) $row->id);
        $this->assertSame('trial', self::statusOf(5));
    }

    /**
     * Control: a query on another model class in between replaced the static
     * slot with a fresh builder, which is why the leak only bit when the next
     * query was on the same model. Passes with and without the fix.
     */
    public function test_a_query_on_another_model_clears_the_leak()
    {
        try {
            ProbeRow::where('organization_id', 'org-a')->where('external_id', null)->first();
        } catch (Throwable) {
        }

        OtherRow::where('id', 1)->first();

        $this->assertCount(2, ProbeRow::where('organization_id', 'org-b')->get());
    }
}
