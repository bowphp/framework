<?php

use \Bow\Database\Database;

class PetMaster extends \Bow\Database\Barry\Model
{
    /**
     * @var string
     */
    protected string $table = "masters";

    /**
     * @var string
     */
    protected string $primaryKey = 'id';

    /**
     * @var bool
     */
    protected bool $timestamps = false;

    public function pets()
    {
        return $this->hasMany(Pet::class);
    }
}

class Pet extends \Bow\Database\Barry\Model
{
    /**
     * @var string
     */
    protected string $table = "pet2s";

    /**
     * @var string
     */
    protected string $primaryKey = 'id';

    /**
     * @var bool
     */
    protected bool $timestamps = false;

    public function master()
    {
        return $this->belongsTo(PetMaster::class);
    }
}

class RelationTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Database
     */
    private $db;

    protected function setUp(): void
    {
        $this->db = $this->configureDatabase();
        $this->db->getPdo()->exec('CREATE TABLE IF NOT EXISTS masters (id INT, name VARCHAR(255))');
        $this->db->getPdo()->exec('CREATE TABLE IF NOT EXISTS pet2s (id INT, name VARCHAR(255), master_id INT, FOREIGN KEY (master_id) REFERENCES masters(id))');
    }

    protected function tearDown(): void
    {
        $this->db->getPdo()->exec('DROP TABLE IF EXISTS pet2s');
        $this->db->getPdo()->exec('DROP TABLE IF EXISTS masters');
    }

    public function testReturnCorrectRelationShip()
    {
        // Create the records
        $this->db->getPdo()->exec("INSERT INTO masters VALUES (1, 'didi')");
        $this->db->getPdo()->exec("INSERT INTO pet2s VALUES (1, 'fluffy', 1)");
        $this->db->getPdo()->exec("INSERT INTO pet2s VALUES (2, 'dolly', 1)");

        $pet = Pet::find(1);
        $master = $pet->master;

        $this->assertInstanceOf(PetMaster::class, $master);
        $this->assertEquals('didi', $master->name);
    }

    private function configureDatabase()
    {
        Database::configure([
            'fetch' => \PDO::FETCH_OBJ,
            'default' => 'sqlite',
            'connections' => [
                'sqlite' => [
                    'driver' => 'sqlite',
                    'database' => __DIR__ . '/data/database.sqlite',
                    'prefix' => ''
                ]
            ]
        ]);

        return Database::getInstance();
    }
}
