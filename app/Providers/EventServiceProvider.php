<?php

namespace App\Providers;

use App\Events\GroceryListChangedEvent;
use App\Events\RegionChangedEvent;
use App\Listeners\RegionChangedListener;
use App\Listeners\UpdateGroceryListListener;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;
class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],

        GroceryListChangedEvent::class => [
            UpdateGroceryListListener::class,
        ],

        RegionChangedEvent::class => [
            RegionChangedListener::class,
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        //
    }
}
