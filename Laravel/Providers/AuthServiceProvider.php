<?php

namespace App\Providers;

use App\Models\BillingAddress;
use App\Models\Connection;
use App\Models\Record;
use App\Models\SubscriptionPlan;
use App\Models\Tag;
use App\Models\User;
use App\Models\UserSubscription;
use App\Models\WebCamera;
use App\Policies\BillingAddressPolicy;
use App\Policies\ConnectionPolicy;
use App\Policies\RecordPolicy;
use App\Policies\SubscriptionPlanPolicy;
use App\Policies\TagPolicy;
use App\Policies\UserPolicy;
use App\Policies\UserSubscriptionPolicy;
use App\Policies\WebCameraPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        User::class             => UserPolicy::class,
        SubscriptionPlan::class => SubscriptionPlanPolicy::class,
        UserSubscription::class => UserSubscriptionPolicy::class,
        Tag::class              => TagPolicy::class,
        Connection::class       => ConnectionPolicy::class,
        Record::class           => RecordPolicy::class,
        WebCamera::class        => WebCameraPolicy::class,
        BillingAddress::class   => BillingAddressPolicy::class
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        //
    }
}
