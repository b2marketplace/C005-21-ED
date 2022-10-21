<?php

namespace Tests\Feature\Services\ProductPriceChange;

use App\Events\ProductPriceChangePending;
use App\Models\Product;
use App\Services\ProductPriceChange\Exceptions\ProductPriceChangeException;
use App\Services\ProductPriceChange\Implementations\InitializeProductPriceChangeService;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class InitializeProductPriceChangeServiceTest extends TestCase
{
    public function testInitializeCreatesProductAndDispatchesEvent()
    {
        $sku = 'SKU-1234';
        $price = 99.99;
        $marketplaceId = 'A1RKKUPIHCS9HS';
        $mockProduct = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockProduct->sku = $sku;
        $mockProduct->price = $price;
        $mockProduct->status = Product::STATUS_PENDING;

        $mockProductModel = $this->mock(Product::class);
        $mockProductModel->shouldReceive('where')
            ->andReturnSelf();
        $mockProductModel->shouldReceive('whereIn')
            ->andReturnSelf();
        $mockProductModel->shouldReceive('update')
            ->andReturn(0);
        $mockProductModel->shouldReceive('create')
            ->once()
            ->with([
                'sku' => $sku,
                'marketplace_id' => $marketplaceId,
                'price' => $price,
                'status' => Product::STATUS_PENDING,
            ])
            ->andReturn($mockProduct);

        Event::fake();

        $service = new InitializeProductPriceChangeService($mockProductModel);
        $result = $service->initialize($sku, $price, $marketplaceId);

        $this->assertSame($mockProduct, $result);
        Event::assertDispatched(ProductPriceChangePending::class, function ($event) use ($mockProduct) {
            return $event->product === $mockProduct;
        });
    }

    public function testInitializeThrowsExceptionOnError()
    {
        $sku = 'SKU-ERROR';
        $price = 10.0;
        $marketplaceId = 'A1RKKUPIHCS9HS';
        $mockProductModel = $this->mock(Product::class);
        $mockProductModel->shouldReceive('where')
            ->andReturnSelf();
        $mockProductModel->shouldReceive('whereIn')
            ->andReturnSelf();
        $mockProductModel->shouldReceive('update')
            ->andReturn(0);
        $mockProductModel->shouldReceive('create')
            ->once()
            ->andThrow(new \Exception('DB error'));

        $service = new InitializeProductPriceChangeService($mockProductModel);
        $this->expectException(ProductPriceChangeException::class);
        $service->initialize($sku, $price, $marketplaceId);
    }
}
