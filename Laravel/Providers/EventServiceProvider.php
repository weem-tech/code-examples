<?php

namespace App\Providers;

use App\Events\ConnectionUpdated;
use App\Events\RecordCreated;
use App\Events\RecordDeleting;
use App\Events\RecordUpdating;
use App\Events\UserSaving;
use App\Events\UserUpdated;
use App\Events\UserUpdating;
use App\Events\WebCameraCreated;
use App\Events\WebCameraDeleting;
use App\Events\WebCameraUpdating;
use App\Listeners\ConnectionUpdated\NotifyConnectorAboutAcceptedConnection;
use App\Listeners\RecordCreated\SetRecordName;
use App\Listeners\UserSaving\BcryptPassword;
use App\Listeners\UserUpdating\CreateDefaultSubscription;
use App\Listeners\UserUpdating\CreateStripeCustomer;
use App\Listeners\WebCameraCreated\SetUserPermissions;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        UserSaving::class => [
            BcryptPassword::class
        ],
        UserUpdating::class => [
            CreateDefaultAvatar::class,
            CreateStripeCustomer::class,
            CreateDefaultSubscription::class
        ]
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
