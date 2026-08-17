<?php

namespace Tests\Feature;

use App\Models\AppNotification;
use App\Models\User;
use App\Services\NotificationService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Kreait\Laravel\Firebase\Facades\Firebase;
use Mockery;
use Tests\TestCase;

class NotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_send_to_user_creates_app_notification(): void
    {
        $user = User::factory()->create(['fcm_token' => null]);
        $service = new NotificationService();

        $result = $service->sendToUser($user, 'Test Title', 'Test Body', ['type' => 'test']);

        $this->assertFalse($result); // FCM skipped in testing
        $this->assertDatabaseHas('app_notifications', [
            'user_id' => $user->id,
            'title' => 'Test Title',
            'body' => 'Test Body',
            'type' => 'test',
        ]);
    }

    public function test_send_to_user_without_fcm_token_still_creates_notification(): void
    {
        $user = User::factory()->create(['fcm_token' => null]);
        $service = new NotificationService();

        $result = $service->sendToUser($user, 'Test Title', 'Test Body');

        $this->assertFalse($result);
        $this->assertDatabaseHas('app_notifications', [
            'user_id' => $user->id,
            'title' => 'Test Title',
        ]);
    }

    public function test_send_to_user_in_non_production_skips_fcm(): void
    {
        $this->app['env'] = 'testing';
        $user = User::factory()->create(['fcm_token' => 'test-token']);
        $service = new NotificationService();

        $result = $service->sendToUser($user, 'Test Title', 'Test Body');

        $this->assertFalse($result);
        $this->assertDatabaseHas('app_notifications', [
            'user_id' => $user->id,
        ]);
    }

    public function test_send_to_users_returns_counts(): void
    {
        $users = User::factory()->count(3)->create(['fcm_token' => null]);
        $service = new NotificationService();

        $result = $service->sendToUsers($users->all(), 'Test', 'Body');

        $this->assertEquals(['sent' => 0, 'failed' => 3], $result);
        $this->assertDatabaseCount('app_notifications', 3);
    }

    public function test_send_to_role_filters_by_fcm_token_and_active(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        
        $activeWithToken = User::factory()->create([
            'fcm_token' => 'token1',
            'is_active' => true,
        ]);
        $activeWithToken->assignRole('Teacher');

        $activeWithoutToken = User::factory()->create([
            'fcm_token' => null,
            'is_active' => true,
        ]);
        $activeWithoutToken->assignRole('Teacher');

        $inactiveWithToken = User::factory()->create([
            'fcm_token' => 'token2',
            'is_active' => false,
        ]);
        $inactiveWithToken->assignRole('Teacher');

        $service = new NotificationService();
        $result = $service->sendToRole('Teacher', 'Test', 'Body');

        $this->assertEquals(['sent' => 0, 'failed' => 1], $result);
        $this->assertDatabaseCount('app_notifications', 1);
    }

    public function test_invalid_token_error_clears_fcm_token(): void
    {
        // This test requires mocking Firebase and setting environment to production
        // Since FCM is skipped in testing, we'll skip this test for now
        $this->markTestSkipped('FCM is skipped in testing environment');
    }

    public function test_data_payload_is_stringified(): void
    {
        $user = User::factory()->create(['fcm_token' => null]);
        $service = new NotificationService();

        $service->sendToUser($user, 'Test', 'Body', [
            'id' => 123,
            'active' => true,
            'score' => 95.5,
            'nested' => ['key' => 'value'],
        ]);

        $notification = AppNotification::where('user_id', $user->id)->first();
        $this->assertIsArray($notification->data);
        $this->assertEquals('123', $notification->data['id']);
        $this->assertEquals('1', $notification->data['active']);
        $this->assertEquals('95.5', $notification->data['score']);
    }

    public function test_click_action_is_added_to_data(): void
    {
        $user = User::factory()->create(['fcm_token' => null]);
        $service = new NotificationService();

        $service->sendToUser($user, 'Test', 'Body', ['type' => 'custom']);

        $notification = AppNotification::where('user_id', $user->id)->first();
        $this->assertEquals('FLUTTER_NOTIFICATION_CLICK', $notification->data['click_action']);
    }

    public function test_default_type_is_general(): void
    {
        $user = User::factory()->create(['fcm_token' => null]);
        $service = new NotificationService();

        $service->sendToUser($user, 'Test', 'Body', []);

        $notification = AppNotification::where('user_id', $user->id)->first();
        $this->assertEquals('general', $notification->type);
    }
}
