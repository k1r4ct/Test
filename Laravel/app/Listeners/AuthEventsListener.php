<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Events\Lockout;
use App\Services\SystemLogService;

class AuthEventsListener
{
    /**
     * Handle user login event.
     */
    public function handleLogin(Login $event): void
    {
        $user = $event->user;

        SystemLogService::auth()->info('User logged in', [
            'user_id' => $user->id,
            'email' => $user->email,
            'name' => trim(($user->name ?? '') . ' ' . ($user->cognome ?? '')),
            'role_id' => $user->role_id ?? null,
            'guard' => $event->guard,
        ]);
    }

    /**
     * Handle user logout event.
     */
    public function handleLogout(Logout $event): void
    {
        $user = $event->user;

        if ($user) {
            SystemLogService::auth()->info('User logged out', [
                'user_id' => $user->id,
                'email' => $user->email,
                'name' => trim(($user->name ?? '') . ' ' . ($user->cognome ?? '')),
                'guard' => $event->guard,
            ]);
        }
    }

    /**
     * Handle failed login attempt.
     */
    public function handleFailed(Failed $event): void
    {
        SystemLogService::auth()->warning('Failed login attempt', [
            'email' => $event->credentials['email'] ?? 'unknown',
            'guard' => $event->guard,
            'user_exists' => $event->user !== null,
        ]);
    }

    /**
     * Handle password reset event.
     */
    public function handlePasswordReset(PasswordReset $event): void
    {
        $user = $event->user;

        SystemLogService::auth()->info('Password reset completed', [
            'user_id' => $user->id,
            'email' => $user->email,
            'name' => trim(($user->name ?? '') . ' ' . ($user->cognome ?? '')),
        ]);
    }

    /**
     * Handle user registration event.
     */
    public function handleRegistered(Registered $event): void
    {
        $user = $event->user;

        SystemLogService::auth()->info('New user registered', [
            'user_id' => $user->id,
            'email' => $user->email,
            'name' => trim(($user->name ?? '') . ' ' . ($user->cognome ?? '')),
            'role_id' => $user->role_id ?? null,
        ]);
    }

    /**
     * Handle email verified event.
     */
    public function handleVerified(Verified $event): void
    {
        $user = $event->user;

        SystemLogService::auth()->info('Email verified', [
            'user_id' => $user->id,
            'email' => $user->email,
        ]);
    }

    /**
     * Handle lockout event (too many failed attempts).
     */
    public function handleLockout(Lockout $event): void
    {
        $request = $event->request;

        SystemLogService::auth()->warning('User locked out due to too many failed attempts', [
            'email' => $request->input('email', 'unknown'),
            'ip_address' => $request->ip(),
        ]);
    }

    /**
     * Register the listeners for the subscriber.
     *
     * @param \Illuminate\Events\Dispatcher $events
     * @return array<string, string>
     */
    public function subscribe($events): array
    {
        return [
            Login::class => 'handleLogin',
            Logout::class => 'handleLogout',
            Failed::class => 'handleFailed',
            PasswordReset::class => 'handlePasswordReset',
            Registered::class => 'handleRegistered',
            Verified::class => 'handleVerified',
            Lockout::class => 'handleLockout',
        ];
    }
}
