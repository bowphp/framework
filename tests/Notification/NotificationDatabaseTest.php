<?php

namespace Bow\Tests\Notification;

use Bow\Database\Database;
use Bow\Tests\Config\TestingConfiguration;

class NotificationDatabaseTest extends \PHPUnit\Framework\TestCase
{
    public static function setUpBeforeClass(): void
    {
        $config = TestingConfiguration::getConfig();

        Database::configure($config["database"]);

        Database::statement("drop table if exists notifications;");
        Database::statement("create table if not exists notifications (
            id int not null primary key auto_increment,
            type text null,
            concern_id int,
            concern_type varchar(500),
            data text null,
            read_at datetime null
        );");
    }

    public function testInsertNotification()
    {
        $result = Database::table('notifications')->insert([
            'type' => 'info',
            'concern_id' => 1,
            'concern_type' => 'user',
            'data' => json_encode(['message' => 'Test notification']),
            'read_at' => null
        ]);

        $this->assertTrue($result);
    }

    public function testRetrieveNotification()
    {
        $notification = Database::table('notifications')->where('id', 1)->first();

        $this->assertNotNull($notification);
        $this->assertEquals('info', $notification->type);
        $this->assertEquals(1, $notification->concern_id);
        $this->assertEquals('user', $notification->concern_type);
        $this->assertEquals(json_encode(['message' => 'Test notification']), $notification->data);
        $this->assertNull($notification->read_at);
    }

    public function testUpdateNotification()
    {
        $result = Database::table('notifications')->where('id', 1)->update([
            'read_at' => date('Y-m-d H:i:s')
        ]);

        $this->assertTrue($result);

        $notification = Database::table('notifications')->where('id', 1)->first();
        $this->assertNotNull($notification->read_at);
    }

    public function testDeleteNotification()
    {
        $result = Database::table('notifications')->where('id', 1)->delete();

        $this->assertTrue($result);

        $notification = Database::table('notifications')->where('id', 1)->first();
        $this->assertNull($notification);
    }
}
