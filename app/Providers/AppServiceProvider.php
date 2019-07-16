<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Ebay\Trading;
use App\Ebay\Fulfillment;
use App\Ebay\Auth\Authorization;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('Ebay\Auth', function () {
            return new Authorization();
        });

        $this->app->singleton('Ebay\Fulfillment', function () {
            return new Fulfillment(
                app('Ebay\Auth')
            );
        });

        $this->app->singleton('Ebay\Trading', function () {
            return new Trading(
                app('Ebay\Auth')
            );
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
