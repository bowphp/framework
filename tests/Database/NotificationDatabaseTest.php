<?php

namespace Bow\Tests\Database;

use Bow\Database\Database;
use Bow\Database\Notification\DatabaseNotification;
use Bow\Tests\Config\TestingConfiguration;
use PHPUnit\Framework\TestCase;

class NotificationDatabaseTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        $config = TestingConfiguration::getConfig();

        Database::configure($config["database"]);

        Database::statement("drop table if exists notifications;");
        // Use actual PDO driver name to handle cases where default config differs from actual connection
        $driver = Database::getPdo()->getAttribute(\PDO::ATTR_DRIVER_NAME);
        $idColumn = match ($driver) {
            'pgsql' => 'id SERIAL PRIMARY KEY',
            'mysql' => 'id INT PRIMARY KEY AUTO_INCREMENT',
            default => 'id INTEGER PRIMARY KEY AUTOINCREMENT'
        };
        Database::statement("create table if not exists notifications (
            $idColumn,
            type text null,
            concern_id int,
            concern_type varchar(500),
            data text null,
            read_at TIMESTAMP null,
            created_at timestamp null default current_timestamp,
            updated_at timestamp null default current_timestamp,
            deleted_at timestamp null
        );");
    }

    public function test_insert_notification()
    {
        $result = Database::table('notifications')->insert([
            'type' => 'success',
            'concern_id' => 1,
            'concern_type' => 'user',
            'data' => json_encode(['message' => 'Test notification']),
            'read_at' => null
        ]);

        $this->assertTrue((bool) $result);
    }

    public function test_retrieve_notification()
    {
        $notification = Database::table('notifications')
            ->where('concern_type', 'user')
            ->where('concern_id', 1)
            ->first();

        $this->assertNotNull($notification);
        $this->assertEquals('success', $notification->type);
        $this->assertEquals(1, $notification->concern_id);
        $this->assertEquals('user', $notification->concern_type);
        $this->assertEquals(json_encode(['message' => 'Test notification']), $notification->data);
        $this->assertNull($notification->read_at);
    }

    public function test_update_notification()
    {
        $result = Database::table('notifications')->where('id', 1)->update([
            'read_at' => date('Y-m-d H:i:s')
        ]);

        $this->assertTrue((bool) $result);

        $notification = Database::table('notifications')->where('id', 1)->first();
        $this->assertNotNull($notification->read_at);
    }

    public function test_delete_notification()
    {
        $result = Database::table('notifications')->where('id', 1)->delete();

        $this->assertTrue((bool) $result);

        $notification = Database::table('notifications')->where('id', 1)->first();
        $this->assertNull($notification);
    }

    public function test_database_notification_model_can_mark_as_read()
    {
        // Insert a new notification
        Database::table('notifications')->insert([
            'type' => 'alert',
            'concern_id' => 2,
            'concern_type' => 'post',
            'data' => json_encode(['message' => 'New comment']),
            'read_at' => null
        ]);

        $notification = DatabaseNotification::where('concern_id', 2)->first();

        $this->assertNotNull($notification);
        $this->assertNull($notification->read_at);

        // Mark as read
        $result = $notification->markAsRead();

        $this->assertTrue((bool) $result);

        // Verify it's marked as read
        $notification = DatabaseNotification::where('concern_id', 2)->first();
        $this->assertNotNull($notification->read_at);
    }

    public function test_database_notification_casts_data_as_array()
    {
        Database::table('notifications')->insert([
            'type' => 'warning',
            'concern_id' => 3,
            'concern_type' => 'user',
            'data' => json_encode(['level' => 'high', 'message' => 'Important update']),
            'read_at' => null
        ]);

        $notification = DatabaseNotification::where('concern_id', 3)->first();

        $this->assertIsArray($notification->data);
        $this->assertEquals('high', $notification->data['level']);
        $this->assertEquals('Important update', $notification->data['message']);
    }

    public function test_can_query_unread_notifications()
    {
        // Insert multiple notifications
        Database::table('notifications')->insert([
            'type' => 'info',
            'concern_id' => 4,
            'concern_type' => 'user',
            'data' => json_encode(['message' => 'Unread notification 1']),
            'read_at' => null
        ]);

        Database::table('notifications')->insert([
            'type' => 'info',
            'concern_id' => 4,
            'concern_type' => 'user',
            'data' => json_encode(['message' => 'Unread notification 2']),
            'read_at' => null
        ]);

        Database::table('notifications')->insert([
            'type' => 'info',
            'concern_id' => 4,
            'concern_type' => 'user',
            'data' => json_encode(['message' => 'Read notification']),
            'read_at' => date('Y-m-d H:i:s')
        ]);

        $unreadCount = DatabaseNotification::where('concern_id', 4)
            ->whereNull('read_at')
            ->count();

        $this->assertEquals(2, $unreadCount);
    }

    public function test_can_filter_notifications_by_type()
    {
        Database::table('notifications')->insert([
            'type' => 'success',
            'concern_id' => 5,
            'concern_type' => 'order',
            'data' => json_encode(['order_id' => 123]),
            'read_at' => null
        ]);

        $notification = DatabaseNotification::where('type', 'success')
            ->where('concern_id', 5)
            ->first();

        $this->assertNotNull($notification);
        $this->assertEquals('success', $notification->type);
        $this->assertEquals(123, $notification->data['order_id']);
    }
}
