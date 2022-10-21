<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\AmazonSpApiCredentials\Contracts\AmazonSpApiCredentialsServiceInterface;
use App\Services\AmazonSpApiCredentials\Implementations\FileAmazonSpApiCredentialsService;
use App\Services\ProductPriceChange\Contracts\InitializeProductPriceChangeServiceInterface;
use App\Services\ProductPriceChange\Implementations\InitializeProductPriceChangeService;
use App\Services\AmazonCatalog\Contracts\AmazonCatalogServiceInterface;
use App\Services\AmazonCatalog\Implementations\AmazonCatalogService;
use App\Services\ProductPriceChange\Contracts\SetProductTypeServiceInterface;
use App\Services\ProductPriceChange\Implementations\SetProductTypeService;

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

        $this->app->bind(
            InitializeProductPriceChangeServiceInterface::class,
            function ($app) {
                return new InitializeProductPriceChangeService($app->make(\App\Models\Product::class));
            }
        );

        $this->app->bind(
            AmazonCatalogServiceInterface::class,
            function ($app) {
                return new AmazonCatalogService(
                    $app->make(\App\Services\AmazonSpApiCredentials\Contracts\AmazonSpApiCredentialsServiceInterface::class)
                );
            }
        );

        $this->app->bind(
            SetProductTypeServiceInterface::class,
            function ($app) {
                return new SetProductTypeService();
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
