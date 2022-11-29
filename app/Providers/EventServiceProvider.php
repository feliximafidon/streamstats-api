<?php

namespace App\Providers;

use App\Listeners\SuccessfulLoginEventHandler;
use App\Models\Stream;
use App\Models\StreamTag;
use App\Observers\StreamObserver;
use App\Observers\StreamTagObserver;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;
use SocialiteProviders\Manager\SocialiteWasCalled;
use SocialiteProviders\Twitch\TwitchExtendSocialite;

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

        SocialiteWasCalled::class => [
            // ... other providers
            TwitchExtendSocialite::class.'@handle',
        ],

        Login::class => [
            SuccessfulLoginEventHandler::class,
        ]
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        StreamTag::observe(StreamTagObserver::class);
        Stream::observe(StreamObserver::class);
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     *
     * @return bool
     */
    public function shouldDiscoverEvents()
    {
        return false;
    }
}
