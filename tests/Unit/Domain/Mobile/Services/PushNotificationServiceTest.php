<?php

declare(strict_types=1);

use App\Domain\Mobile\Models\MobileDevice;
use App\Domain\Mobile\Models\MobilePushNotification;
use App\Domain\Mobile\Services\PushNotificationService;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Exception\Messaging\NotFound;
use Kreait\Firebase\Messaging\CloudMessage;

uses(Tests\TestCase::class);

describe('PushNotificationService', function () {
    beforeEach(function () {
        $this->messaging = Mockery::mock(Messaging::class);
        $this->service = new PushNotificationService($this->messaging);
    });

    it('sends notification via FCM v1 API', function () {
        $user = App\Models\User::factory()->create();
        $device = MobileDevice::factory()->create([
            'user_id'    => $user->id,
            'push_token' => 'valid-fcm-token',
            'platform'   => 'android',
            'is_blocked' => false,
        ]);
        $notification = MobilePushNotification::create([
            'user_id'           => $user->id,
            'mobile_device_id'  => $device->id,
            'notification_type' => MobilePushNotification::TYPE_GENERAL,
            'title'             => 'Test Title',
            'body'              => 'Test Body',
            'data'              => ['key' => 'value'],
            'status'            => MobilePushNotification::STATUS_PENDING,
        ]);

        $this->messaging
            ->shouldReceive('send')
            ->once()
            ->with(Mockery::type(CloudMessage::class))
            ->andReturn(['name' => 'projects/test/messages/12345']);

        $result = $this->service->sendNotification($notification);

        expect($result['status'])->toBe('sent');
        expect($result['message_id'])->toBe('projects/test/messages/12345');
        expect($result['notification_id'])->toBe($notification->id);
    });

    it('skips when FCM not configured', function () {
        $service = new PushNotificationService(null);

        $user = App\Models\User::factory()->create();
        $device = MobileDevice::factory()->create([
            'user_id'    => $user->id,
            'push_token' => 'some-token',
            'platform'   => 'android',
            'is_blocked' => false,
        ]);
        $notification = MobilePushNotification::create([
            'user_id'           => $user->id,
            'mobile_device_id'  => $device->id,
            'notification_type' => MobilePushNotification::TYPE_GENERAL,
            'title'             => 'Test',
            'body'              => 'Test',
            'data'              => [],
            'status'            => MobilePushNotification::STATUS_PENDING,
        ]);

        $result = $service->sendNotification($notification);

        expect($result['status'])->toBe('skipped');
        expect($result['message'])->toBe('FCM not configured');
    });

    it('handles invalid token by clearing push_token', function () {
        $user = App\Models\User::factory()->create();
        $device = MobileDevice::factory()->create([
            'user_id'    => $user->id,
            'push_token' => 'invalid-token',
            'platform'   => 'android',
            'is_blocked' => false,
        ]);
        $notification = MobilePushNotification::create([
            'user_id'           => $user->id,
            'mobile_device_id'  => $device->id,
            'notification_type' => MobilePushNotification::TYPE_GENERAL,
            'title'             => 'Test',
            'body'              => 'Test',
            'data'              => [],
            'status'            => MobilePushNotification::STATUS_PENDING,
        ]);

        $this->messaging
            ->shouldReceive('send')
            ->once()
            ->andThrow(NotFound::becauseTokenNotFound('invalid-token'));

        $result = $this->service->sendNotification($notification);

        expect($result['status'])->toBe('failed');
        expect($result['error'])->toContain('Invalid token');

        $device->refresh();
        expect($device->push_token)->toBeNull();
    });

    it('handles FCM failure gracefully', function () {
        $user = App\Models\User::factory()->create();
        $device = MobileDevice::factory()->create([
            'user_id'    => $user->id,
            'push_token' => 'some-token',
            'platform'   => 'android',
            'is_blocked' => false,
        ]);
        $notification = MobilePushNotification::create([
            'user_id'           => $user->id,
            'mobile_device_id'  => $device->id,
            'notification_type' => MobilePushNotification::TYPE_GENERAL,
            'title'             => 'Test',
            'body'              => 'Test',
            'data'              => [],
            'status'            => MobilePushNotification::STATUS_PENDING,
        ]);

        $exception = new Kreait\Firebase\Exception\Messaging\ServerError('FCM server error');
        $this->messaging
            ->shouldReceive('send')
            ->once()
            ->andThrow($exception);

        $result = $this->service->sendNotification($notification);

        expect($result['status'])->toBe('failed');
        expect($result['error'])->toContain('FCM server error');

        // Token should NOT be cleared for server errors
        $device->refresh();
        expect($device->push_token)->toBe('some-token');
    });

    it('includes android config for android devices', function () {
        $user = App\Models\User::factory()->create();
        $device = MobileDevice::factory()->create([
            'user_id'    => $user->id,
            'push_token' => 'android-token',
            'platform'   => 'android',
            'is_blocked' => false,
        ]);
        $notification = MobilePushNotification::create([
            'user_id'           => $user->id,
            'mobile_device_id'  => $device->id,
            'notification_type' => MobilePushNotification::TYPE_GENERAL,
            'title'             => 'Test',
            'body'              => 'Test',
            'data'              => [],
            'status'            => MobilePushNotification::STATUS_PENDING,
        ]);

        $this->messaging
            ->shouldReceive('send')
            ->once()
            ->with(Mockery::on(function (CloudMessage $message) {
                $serialized = $message->jsonSerialize();

                return isset($serialized['android'])
                    && ($serialized['android']['notification']['channel_id'] ?? null) === 'finaegis_default';
            }))
            ->andReturn(['name' => 'projects/test/messages/android-1']);

        $result = $this->service->sendNotification($notification);

        expect($result['status'])->toBe('sent');
    });

    it('includes apns config for ios devices', function () {
        $user = App\Models\User::factory()->create();
        $device = MobileDevice::factory()->create([
            'user_id'    => $user->id,
            'push_token' => 'ios-token',
            'platform'   => 'ios',
            'is_blocked' => false,
        ]);
        $notification = MobilePushNotification::create([
            'user_id'           => $user->id,
            'mobile_device_id'  => $device->id,
            'notification_type' => MobilePushNotification::TYPE_GENERAL,
            'title'             => 'Test',
            'body'              => 'Test',
            'data'              => [],
            'status'            => MobilePushNotification::STATUS_PENDING,
        ]);

        $this->messaging
            ->shouldReceive('send')
            ->once()
            ->with(Mockery::on(function (CloudMessage $message) {
                $serialized = $message->jsonSerialize();

                return isset($serialized['apns']);
            }))
            ->andReturn(['name' => 'projects/test/messages/ios-1']);

        $result = $this->service->sendNotification($notification);

        expect($result['status'])->toBe('sent');
    });

    it('returns error when device has no push token', function () {
        $user = App\Models\User::factory()->create();
        $device = MobileDevice::factory()->create([
            'user_id'    => $user->id,
            'push_token' => null,
            'platform'   => 'android',
            'is_blocked' => false,
        ]);
        $notification = MobilePushNotification::create([
            'user_id'           => $user->id,
            'mobile_device_id'  => $device->id,
            'notification_type' => MobilePushNotification::TYPE_GENERAL,
            'title'             => 'Test',
            'body'              => 'Test',
            'data'              => [],
            'status'            => MobilePushNotification::STATUS_PENDING,
        ]);

        $result = $this->service->sendNotification($notification);

        expect($result['status'])->toBe('error');
        expect($result['message'])->toBe('Device or push token not found');

        $this->messaging->shouldNotHaveReceived('send');
    });
});
