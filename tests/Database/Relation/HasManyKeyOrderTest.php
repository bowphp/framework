<?php

namespace Bow\Tests\Database\Relation;

use Bow\Cache\Cache;
use Bow\Database\Database;
use Bow\Tests\Config\TestingConfiguration;
use Bow\Tests\Database\Stubs\PetMasterModelStub;
use Bow\Tests\Database\Stubs\PetModelStub;

/**
 * Locks in the hasMany() signature `(related, foreign_key, local_key)`, now
 * consistent with hasOne()/belongsTo(): the foreign key is the column on the
 * related table, the local key is the referenced column on the parent.
 */
class HasManyKeyOrderTest extends \PHPUnit\Framework\TestCase
{
    private static bool $configured = false;

    public static function setUpBeforeClass(): void
    {
        if (!static::$configured) {
            $config = TestingConfiguration::getConfig();
            Database::configure($config["database"]);
            Cache::configure($config["cache"]);
            static::$configured = true;
        }
    }

    public function setUp(): void
    {
        ob_start();
        Database::connection('sqlite');
    }

    public function tearDown(): void
    {
        ob_get_clean();
    }

    public function test_hasmany_filters_on_foreign_key_with_new_arg_order()
    {
        $master = new PetMasterModelStub(['id' => 7]);

        // (related, foreign_key, local_key)
        $relation = $master->hasMany(PetModelStub::class, 'master_id', 'id');

        $this->assertStringContainsString(
            'where master_id = ?',
            strtolower($relation->toSql())
        );
    }

    public function test_hasmany_default_keys_filter_on_foreign_key_column()
    {
        $master = new PetMasterModelStub(['id' => 7]);

        // No keys: the foreign key defaults to the parent-derived column that
        // lives on the related table (pet_masters -> pet_master_id), NOT the
        // related table's own key (pet_id) nor the parent primary key (id).
        $relation = $master->hasMany(PetModelStub::class);

        $sql = strtolower($relation->toSql());

        $this->assertStringContainsString('where pet_master_id = ?', $sql);
        $this->assertStringNotContainsString('where pet_id = ?', $sql);
        $this->assertStringNotContainsString('where id = ?', $sql);
    }

    public function test_hasone_and_hasmany_take_keys_in_the_same_order()
    {
        $master = new PetMasterModelStub(['id' => 7]);

        $one = $master->hasOne(PetModelStub::class, 'master_id', 'id');
        $many = $master->hasMany(PetModelStub::class, 'master_id', 'id');

        $this->assertStringContainsString('where master_id = ?', strtolower($one->toSql()));
        $this->assertStringContainsString('where master_id = ?', strtolower($many->toSql()));
    }
}
