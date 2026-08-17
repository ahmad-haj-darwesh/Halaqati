<?php

namespace Tests\Feature;

use App\Models\AppNotification;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_unauthenticated_cannot_access_notifications(): void
    {
        $this->getJson('/api/notifications')->assertUnauthorized();
        $this->getJson('/api/notifications/unread-count')->assertUnauthorized();
        $this->postJson('/api/notifications/read-all')->assertUnauthorized();
        $this->postJson('/api/notifications/1/read')->assertUnauthorized();
    }

    public function test_user_can_list_their_notifications(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Teacher');
        $token = $user->createToken('mobile-app')->plainTextToken;

        AppNotification::factory()->count(5)->create(['user_id' => $user->id]);
        AppNotification::factory()->count(3)->create(); // Other user's notifications

        $response = $this->withToken($token)->getJson('/api/notifications');

        $response->assertOk()
            ->assertJsonStructure([
                'data',
                'total',
                'current_page',
                'last_page',
                'unread_count',
            ])
            ->assertJsonCount(5, 'data');
    }

    public function test_notifications_are_paginated(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Teacher');
        $token = $user->createToken('mobile-app')->plainTextToken;

        AppNotification::factory()->count(25)->create(['user_id' => $user->id]);

        $response = $this->withToken($token)->getJson('/api/notifications');

        $response->assertOk()
            ->assertJsonCount(20, 'data')
            ->assertJsonPath('total', 25)
            ->assertJsonPath('last_page', 2);
    }

    public function test_notification_response_includes_required_fields(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Teacher');
        $token = $user->createToken('mobile-app')->plainTextToken;

        $notification = AppNotification::factory()->create([
            'user_id' => $user->id,
            'title' => 'Test Title',
            'body' => 'Test Body',
            'type' => 'test_type',
            'data' => ['key' => 'value'],
        ]);

        $response = $this->withToken($token)->getJson('/api/notifications');

        $response->assertOk();
        $data = $response->json('data.0');
        
        $this->assertEquals($notification->id, $data['id']);
        $this->assertEquals('Test Title', $data['title']);
        $this->assertEquals('Test Body', $data['body']);
        $this->assertEquals('test_type', $data['type']);
        $this->assertEquals(['key' => 'value'], $data['data']);
        $this->assertArrayHasKey('is_read', $data);
        $this->assertArrayHasKey('created_at', $data);
        $this->assertArrayHasKey('created_at_human', $data);
    }

    public function test_unread_count_endpoint(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Teacher');
        $token = $user->createToken('mobile-app')->plainTextToken;

        AppNotification::factory()->count(3)->create([
            'user_id' => $user->id,
            'read_at' => null,
        ]);
        AppNotification::factory()->count(2)->create([
            'user_id' => $user->id,
            'read_at' => now(),
        ]);

        $response = $this->withToken($token)->getJson('/api/notifications/unread-count');

        $response->assertOk()
            ->assertJson(['count' => 3]);
    }

    public function test_mark_single_notification_as_read(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Teacher');
        $token = $user->createToken('mobile-app')->plainTextToken;

        $notification = AppNotification::factory()->create([
            'user_id' => $user->id,
            'read_at' => null,
        ]);

        $response = $this->withToken($token)->postJson("/api/notifications/{$notification->id}/read");

        $response->assertOk()
            ->assertJson(['message' => 'تم تعليم الإشعار كمقروء']);

        $notification->refresh();
        $this->assertNotNull($notification->read_at);
    }

    public function test_cannot_mark_other_users_notification_as_read(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Teacher');
        $token = $user->createToken('mobile-app')->plainTextToken;

        $otherUser = User::factory()->create();
        $notification = AppNotification::factory()->create([
            'user_id' => $otherUser->id,
            'read_at' => null,
        ]);

        $response = $this->withToken($token)->postJson("/api/notifications/{$notification->id}/read");

        $response->assertOk(); // No error, but no update
        $notification->refresh();
        $this->assertNull($notification->read_at);
    }

    public function test_mark_all_notifications_as_read(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Teacher');
        $token = $user->createToken('mobile-app')->plainTextToken;

        AppNotification::factory()->count(5)->create([
            'user_id' => $user->id,
            'read_at' => null,
        ]);
        AppNotification::factory()->count(2)->create([
            'user_id' => $user->id,
            'read_at' => now(),
        ]);

        $response = $this->withToken($token)->postJson('/api/notifications/read-all');

        $response->assertOk()
            ->assertJson(['message' => 'تم تعليم جميع الإشعارات كمقروءة']);

        $unreadCount = AppNotification::where('user_id', $user->id)->unread()->count();
        $this->assertEquals(0, $unreadCount);
    }

    public function test_read_all_only_affects_current_user(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Teacher');
        $token = $user->createToken('mobile-app')->plainTextToken;

        $otherUser = User::factory()->create();
        AppNotification::factory()->count(3)->create([
            'user_id' => $otherUser->id,
            'read_at' => null,
        ]);

        AppNotification::factory()->count(2)->create([
            'user_id' => $user->id,
            'read_at' => null,
        ]);

        $this->withToken($token)->postJson('/api/notifications/read-all');

        $this->assertEquals(3, AppNotification::where('user_id', $otherUser->id)->unread()->count());
        $this->assertEquals(0, AppNotification::where('user_id', $user->id)->unread()->count());
    }

    public function test_notifications_list_includes_unread_count(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Teacher');
        $token = $user->createToken('mobile-app')->plainTextToken;

        AppNotification::factory()->count(4)->create([
            'user_id' => $user->id,
            'read_at' => null,
        ]);

        $response = $this->withToken($token)->getJson('/api/notifications');

        $response->assertOk()
            ->assertJsonPath('unread_count', 4);
    }

    public function test_is_read_field_in_response(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Teacher');
        $token = $user->createToken('mobile-app')->plainTextToken;

        $readNotification = AppNotification::factory()->create([
            'user_id' => $user->id,
            'read_at' => now(),
        ]);
        $unreadNotification = AppNotification::factory()->create([
            'user_id' => $user->id,
            'read_at' => null,
        ]);

        $response = $this->withToken($token)->getJson('/api/notifications');
        $data = $response->json('data');

        $readItem = collect($data)->firstWhere('id', $readNotification->id);
        $unreadItem = collect($data)->firstWhere('id', $unreadNotification->id);

        $this->assertTrue($readItem['is_read']);
        $this->assertFalse($unreadItem['is_read']);
    }
}
