<?php

namespace App\Providers;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;
use Laravel\Cashier\Cashier;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        Cashier::ignoreMigrations();
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        App::environment('local') ? URL::forceScheme('http') : URL::forceScheme('https');
        $this->customValidators();
    }

    private function customValidators()
    {
        Validator::extend('exists_on_model', 'App\Http\Validators\BaseValidator@existsOnModel');
        Validator::extend('not_exists_on_model', 'App\Http\Validators\BaseValidator@notExistsOnModel');
        Validator::extend('exists_on_connection', 'App\Http\Validators\ExistsOnConnectionValidator@existsOnConnection');
        Validator::extend('not_exists_on_connection', 'App\Http\Validators\ExistsOnConnectionValidator@notExistsOnConnection');
        Validator::extend('exists_on_blocked', 'App\Http\Validators\ExistsOnBlockedValidator@existsOnBlocked');
        Validator::extend('not_exists_on_blocked', 'App\Http\Validators\ExistsOnBlockedValidator@notExistsOnBlocked');
        Validator::extend('exists_in_media', 'App\Http\Validators\ExistsMediaValidator@existsInMedia');
        Validator::extend('media_duration', 'App\Http\Validators\ExistsMediaValidator@mediaDuration');
    }
}
