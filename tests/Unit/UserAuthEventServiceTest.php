<?php

namespace Tests\Unit\Services\Auth;

use App\Enums\Auth\UserAuthEventType;
use App\Models\User;
use App\Models\UserAuthEvent;
use App\Services\Auth\UserAuthEventService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class UserAuthEventServiceTest extends TestCase
{
    use RefreshDatabase;

    private UserAuthEventService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new UserAuthEventService();
    }

    private function createUser(): User
    {
        return User::factory()->create();
    }

    public function test_it_creates_authentication_event(): void
    {
        $user = $this->createUser();

        $event = $this->service->log(
            user: $user,
            eventType: UserAuthEventType::LOGIN,
            ip: '192.168.1.20',
            userAgent: 'PHPUnit Service Agent',
            isSuccess: true,
        );

        $this->assertInstanceOf(UserAuthEvent::class, $event);

        $this->assertDatabaseHas('user_auth_events', [
            'id' => $event->id,
            'user_id' => $user->id,
            'event_type' => UserAuthEventType::LOGIN->value,
            'ip_address' => '192.168.1.20',
            'user_agent' => 'PHPUnit Service Agent',
            'is_success' => true,
        ]);
    }

    public function test_it_creates_failed_authentication_event(): void
    {
        $user = $this->createUser();

        $event = $this->service->log(
            user: $user,
            eventType: UserAuthEventType::FAILED_LOGIN,
            ip: '192.168.1.21',
            userAgent: 'PHPUnit Failed Agent',
            isSuccess: false,
        );

        $this->assertNotNull($event);

        $this->assertDatabaseHas('user_auth_events', [
            'user_id' => $user->id,
            'event_type' => UserAuthEventType::FAILED_LOGIN->value,
            'is_success' => false,
        ]);
    }

    public function test_it_uses_unknown_when_ip_and_user_agent_are_null(): void
    {
        $user = $this->createUser();

        $event = $this->service->log(
            user: $user,
            eventType: UserAuthEventType::LOGIN,
            ip: null,
            userAgent: null,
        );

        $this->assertNotNull($event);

        $this->assertDatabaseHas('user_auth_events', [
            'user_id' => $user->id,
            'event_type' => UserAuthEventType::LOGIN->value,
            'ip_address' => 'unknown',
            'user_agent' => 'unknown',
            'is_success' => true,
        ]);
    }

    public function test_it_returns_null_and_logs_warning_when_event_creation_fails(): void
    {
        Log::spy();

        $user = $this->createUser();

        UserAuthEvent::creating(function (): void {
            throw new RuntimeException('Simulated database failure');
        });

        try {
            $event = $this->service->log(
                user: $user,
                eventType: UserAuthEventType::LOGIN,
                ip: '192.168.1.22',
                userAgent: 'PHPUnit Exception Agent',
            );

            $this->assertNull($event);

            $this->assertDatabaseCount('user_auth_events', 0);

            Log::shouldHaveReceived('warning')
                ->once()
                ->with(
                    'Failed to create authentication log event.',
                    [
                        'user_id' => $user->id,
                        'event_type' => UserAuthEventType::LOGIN->value,
                    ],
                );
        } finally {
            UserAuthEvent::flushEventListeners();
        }
    }
}
