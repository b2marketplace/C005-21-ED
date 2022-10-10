<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\AmazonSpApiCredentials\Contracts\AmazonSpApiCredentialsServiceInterface;
use App\Services\AmazonSpApiCredentials\Implementations\FileAmazonSpApiCredentialsService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(
            AmazonSpApiCredentialsServiceInterface::class,
            function ($app) {
                return new FileAmazonSpApiCredentialsService();
            }
        );
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
