<?php

use App\Events\NotificationBroadcast;
use App\Models\Device;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->user = User::factory()->create([
        'password' => Hash::make('Password123!'),
    ]);

    $this->otherUser = User::factory()->create();

    Event::fake();
});

describe('List Notifications', function () {
    it('authenticated user can view their notifications', function () {
        $device = Device::factory()->create(['user_id' => $this->user->id]);

        Notification::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'device_id' => $device->id,
        ]);

        // Create notification for another user (should not appear)
        Notification::factory()->create([
            'user_id' => $this->otherUser->id,
            'device_id' => $device->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/notifications');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'user_id',
                        'device_id',
                        'title',
                        'message',
                        'type',
                        'is_read',
                        'created_at',
                        'time',
                    ],
                ],
            ]);

        expect(count($response->json('data')))->toBe(3);
    });

    it('time is displayed in correct format', function () {
        $device = Device::factory()->create(['user_id' => $this->user->id]);

        $notification = Notification::factory()->create([
            'user_id' => $this->user->id,
            'device_id' => $device->id,
            'created_at' => now()->setTime(14, 30), // 2:30 PM
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/notifications');

        $response->assertStatus(200);

        $notificationData = collect($response->json('data'))->firstWhere('id', $notification->id);
        expect($notificationData)->not->toBeNull()
            ->and($notificationData['time'])->toMatch('/^\d{1,2}:\d{2} (AM|PM)$/'); // Format: h:i A
    });
});

describe('Get Unread Count', function () {
    it('unread count matches actual unread notifications', function () {
        $device = Device::factory()->create(['user_id' => $this->user->id]);

        // Create 3 unread notifications
        Notification::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'device_id' => $device->id,
            'is_read' => false,
        ]);

        // Create 2 read notifications
        Notification::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'device_id' => $device->id,
            'is_read' => true,
        ]);

        // Create notification for another user (should not count)
        Notification::factory()->create([
            'user_id' => $this->otherUser->id,
            'device_id' => $device->id,
            'is_read' => false,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/notifications/unread-count');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'unread_count',
            ])
            ->assertJson([
                'unread_count' => 3,
            ]);
    });
});

describe('Create Notification', function () {
    it('user can create a notification successfully', function () {
        $device = Device::factory()->create(['user_id' => $this->user->id]);

        $notificationData = [
            'device_id' => $device->id,
            'title' => 'Test Notification',
            'message' => 'This is a test notification message',
            'type' => 'info',
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/create-notifications', $notificationData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'user_id',
                    'device_id',
                    'title',
                    'message',
                    'type',
                    'time',
                ],
            ])
            ->assertJson([
                'message' => 'Notification created',
                'data' => [
                    'title' => 'Test Notification',
                    'message' => 'This is a test notification message',
                    'type' => 'info',
                ],
            ]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->user->id,
            'device_id' => $device->id,
            'title' => 'Test Notification',
            'message' => 'This is a test notification message',
            'type' => 'info',
            'is_read' => false,
        ]);
    });

    it('notification appears in listing after creation', function () {
        $device = Device::factory()->create(['user_id' => $this->user->id]);

        $notificationData = [
            'device_id' => $device->id,
            'title' => 'New Notification',
            'message' => 'New notification message',
            'type' => 'warning',
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/create-notifications', $notificationData);

        $response->assertStatus(201);

        $notificationId = $response->json('data.id');

        // Check it appears in listing
        $listResponse = $this->actingAs($this->user)
            ->getJson('/api/v1/notifications');

        $listResponse->assertStatus(200);
        $notification = collect($listResponse->json('data'))->firstWhere('id', $notificationId);
        expect($notification)->not->toBeNull()
            ->and($notification['title'])->toBe('New Notification');
    });

    it('broadcast event is triggered', function () {
        $device = Device::factory()->create(['user_id' => $this->user->id]);

        $notificationData = [
            'device_id' => $device->id,
            'title' => 'Broadcast Test',
            'message' => 'Testing broadcast',
            'type' => 'success',
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/create-notifications', $notificationData);

        $response->assertStatus(201);

        Event::assertDispatched(NotificationBroadcast::class);
    });
});

describe('Mark As Read', function () {
    it('user can mark a notification as read', function () {
        $device = Device::factory()->create(['user_id' => $this->user->id]);

        $notification = Notification::factory()->create([
            'user_id' => $this->user->id,
            'device_id' => $device->id,
            'is_read' => false,
        ]);

        $response = $this->actingAs($this->user)
            ->patchJson('/api/v1/notifications/' . $notification->id . '/mark-as-read');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'is_read',
                ],
            ])
            ->assertJson([
                'message' => 'Notification marked as read',
                'data' => [
                    'id' => $notification->id,
                    'is_read' => true,
                ],
            ]);

        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'is_read' => true,
        ]);
    });

    it('cannot mark another user\'s notification as read (authorization)', function () {
        $device = Device::factory()->create(['user_id' => $this->otherUser->id]);

        $notification = Notification::factory()->create([
            'user_id' => $this->otherUser->id,
            'device_id' => $device->id,
            'is_read' => false,
        ]);

        $response = $this->actingAs($this->user)
            ->patchJson('/api/v1/notifications/' . $notification->id . '/mark-as-read');

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Notification not found',
            ]);

        // Verify notification is still unread
        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'is_read' => false,
        ]);
    });

    it('notification is updated in listing/unread count', function () {
        $device = Device::factory()->create(['user_id' => $this->user->id]);

        $notification = Notification::factory()->create([
            'user_id' => $this->user->id,
            'device_id' => $device->id,
            'is_read' => false,
        ]);

        // Check unread count before
        $unreadBefore = $this->actingAs($this->user)
            ->getJson('/api/v1/notifications/unread-count')
            ->json('unread_count');

        // Mark as read
        $response = $this->actingAs($this->user)
            ->patchJson('/api/v1/notifications/' . $notification->id . '/mark-as-read');

        $response->assertStatus(200);

        // Check unread count after
        $unreadAfter = $this->actingAs($this->user)
            ->getJson('/api/v1/notifications/unread-count')
            ->json('unread_count');

        expect($unreadAfter)->toBe($unreadBefore - 1);

        // Check notification in listing
        $listResponse = $this->actingAs($this->user)
            ->getJson('/api/v1/notifications');

        $listResponse->assertStatus(200);
        $notificationData = collect($listResponse->json('data'))->firstWhere('id', $notification->id);
        expect($notificationData)->not->toBeNull()
            ->and($notificationData['is_read'])->toBeTrue();
    });
});

describe('Mark All As Read', function () {
    it('user can mark all notifications as read', function () {
        $device = Device::factory()->create(['user_id' => $this->user->id]);

        // Create 3 unread notifications
        Notification::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'device_id' => $device->id,
            'is_read' => false,
        ]);

        // Create 1 read notification (should remain read)
        Notification::factory()->create([
            'user_id' => $this->user->id,
            'device_id' => $device->id,
            'is_read' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/notifications/mark-all-read');

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'All notifications marked as read',
            ]);

        // Verify all notifications are read
        $unreadCount = Notification::where('user_id', $this->user->id)
            ->where('is_read', false)
            ->count();

        expect($unreadCount)->toBe(0);
    });

    it('unread count becomes zero after marking all', function () {
        $device = Device::factory()->create(['user_id' => $this->user->id]);

        // Create 5 unread notifications
        Notification::factory()->count(5)->create([
            'user_id' => $this->user->id,
            'device_id' => $device->id,
            'is_read' => false,
        ]);

        // Verify unread count before
        $unreadBefore = $this->actingAs($this->user)
            ->getJson('/api/v1/notifications/unread-count')
            ->json('unread_count');

        expect($unreadBefore)->toBe(5);

        // Mark all as read
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/notifications/mark-all-read');

        $response->assertStatus(200);

        // Verify unread count after
        $unreadAfter = $this->actingAs($this->user)
            ->getJson('/api/v1/notifications/unread-count')
            ->json('unread_count');

        expect($unreadAfter)->toBe(0);
    });
});
