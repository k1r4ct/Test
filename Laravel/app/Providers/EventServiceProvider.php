<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Database\Events\QueryExecuted;

// Log Listeners
use App\Listeners\AuthEventsListener;
use App\Listeners\EmailEventsListener;
use App\Listeners\QueryListener;
use App\Listeners\JobEventsListener;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        
        // Database query events
        QueryExecuted::class => [
            QueryListener::class,
        ],
    ];

    /**
     * The subscriber classes to register.
     * 
     * Subscribers can listen to multiple events in a single class.
     *
     * @var array<int, class-string>
     */
    protected $subscribe = [
        AuthEventsListener::class,
        EmailEventsListener::class,
        JobEventsListener::class,
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
