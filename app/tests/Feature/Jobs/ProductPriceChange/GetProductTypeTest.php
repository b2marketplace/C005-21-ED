<?php

namespace Tests\Feature\Jobs\ProductPriceChange;

use App\Events\AmazonSpApiCredentialsExpired;
use App\Events\ProductTypeRetrieved;
use App\Jobs\ProductPriceChange\GetProductType;
use App\Models\Product;
use App\Services\AmazonCatalog\Contracts\AmazonCatalogServiceInterface;
use App\Services\AmazonCatalog\Exceptions\AmazonCatalogProductTypeNotFoundException;
use App\Services\AmazonCatalog\Exceptions\AmazonCatalogThrottledException;
use App\Services\AmazonCatalog\Exceptions\AmazonCatalogUnauthorizedException;
use App\Services\AmazonCatalog\Exceptions\AmazonCatalogWaitLockException;
use App\Services\ProductPriceChange\Contracts\SetProductTypeServiceInterface;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class GetProductTypeTest extends TestCase
{
    public function testProductTypeSuccessDispatchesEvent()
    {
        $product = Product::factory()->make(['status' => Product::STATUS_PENDING]);
        $catalogService = $this->mock(AmazonCatalogServiceInterface::class);
        $setProductTypeService = $this->mock(SetProductTypeServiceInterface::class);
        $this->app->instance(AmazonCatalogServiceInterface::class, $catalogService);
        $this->app->instance(SetProductTypeServiceInterface::class, $setProductTypeService);
        $catalogService->shouldReceive('getProductTypeBySku')
            ->once()
            ->with($product->sku, $product->marketplace_id)
            ->andReturn('MY_TYPE');
        $setProductTypeService->shouldReceive('setProductType')
            ->once()
            ->with($product, 'MY_TYPE');
        Event::fake([ProductTypeRetrieved::class]);
        $job = new GetProductType($product);
        $job->handle($catalogService, $setProductTypeService);
        Event::assertDispatched(ProductTypeRetrieved::class, function ($event) use ($product) {
            return $event->product === $product;
        });
    }

    public function testProductTypeNotFoundMarksFailed()
    {
        $product = Product::factory()->make(['status' => Product::STATUS_PENDING]);
        $catalogService = $this->mock(AmazonCatalogServiceInterface::class);
        $setProductTypeService = $this->mock(SetProductTypeServiceInterface::class);
        $this->app->instance(AmazonCatalogServiceInterface::class, $catalogService);
        $this->app->instance(SetProductTypeServiceInterface::class, $setProductTypeService);
        $catalogService->shouldReceive('getProductTypeBySku')
            ->once()
            ->andThrow(new AmazonCatalogProductTypeNotFoundException('not found'));
        $setProductTypeService->shouldNotReceive('setProductType');
        Log::shouldReceive('error')->once();
        $job = new GetProductType($product);
        $job->handle($catalogService, $setProductTypeService);
        $this->assertEquals(Product::STATUS_FAILED, $product->status);
    }

    public function testUnauthorizedDispatchesCredentialsExpiredAndRelease()
    {
        $product = Product::factory()->make(['status' => Product::STATUS_PENDING]);
        $catalogService = $this->mock(AmazonCatalogServiceInterface::class);
        $setProductTypeService = $this->mock(SetProductTypeServiceInterface::class);
        $this->app->instance(AmazonCatalogServiceInterface::class, $catalogService);
        $this->app->instance(SetProductTypeServiceInterface::class, $setProductTypeService);
        $catalogService->shouldReceive('getProductTypeBySku')
            ->once()
            ->andThrow(new AmazonCatalogUnauthorizedException('unauthorized'));
        $setProductTypeService->shouldNotReceive('setProductType');
        Event::fake([AmazonSpApiCredentialsExpired::class]);
        Log::shouldReceive('error')->once();
        // Usar Mockery para el job y mockear release
        $job = \Mockery::mock(GetProductType::class, [$product])->makePartial();
        $job->shouldAllowMockingProtectedMethods();
        $job->shouldReceive('release')->once();
        $job->handle($catalogService, $setProductTypeService);
        Event::assertDispatched(AmazonSpApiCredentialsExpired::class);
    }

    public function testThrottledOrWaitLockRelease()
    {
        $product = Product::factory()->make(['status' => Product::STATUS_PENDING]);
        $catalogService = $this->mock(AmazonCatalogServiceInterface::class);
        $setProductTypeService = $this->mock(SetProductTypeServiceInterface::class);
        $this->app->instance(AmazonCatalogServiceInterface::class, $catalogService);
        $this->app->instance(SetProductTypeServiceInterface::class, $setProductTypeService);
        $catalogService->shouldReceive('getProductTypeBySku')
            ->once()
            ->andThrow(new AmazonCatalogThrottledException('throttled'));
        $setProductTypeService->shouldNotReceive('setProductType');
        Log::shouldReceive('warning')->once();
        // Usar Mockery para el job y mockear release
        $job = \Mockery::mock(GetProductType::class, [$product])->makePartial();
        $job->shouldAllowMockingProtectedMethods();
        $job->shouldReceive('release')->once();
        $job->handle($catalogService, $setProductTypeService);
    }
}
