<?php

namespace App\Services\ProductPriceChange\Implementations;

use App\Models\Product;
use App\Events\ProductPriceChangePending;
use App\Services\ProductPriceChange\Contracts\InitializeProductPriceChangeServiceInterface;
use App\Services\ProductPriceChange\Exceptions\ProductPriceChangeException;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\DB;

class InitializeProductPriceChangeService implements InitializeProductPriceChangeServiceInterface
{
    /**
     * @var object
     */
    protected $productModel;

    /**
     * Constructor.
     *
     * @param object $productModel
     */
    public function __construct($productModel)
    {
        $this->productModel = $productModel;
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(string $sku, float $price, string $marketplaceId): Product
    {
        try {
            return DB::transaction(function () use ($sku, $price, $marketplaceId) {
                // Cancel previous pending or in-progress products for same sku and marketplace_id
                $this->productModel->where('sku', $sku)
                    ->where('marketplace_id', $marketplaceId)
                    ->whereIn('status', [
                        Product::STATUS_PENDING,
                        Product::STATUS_IN_PROGRESS,
                    ])
                    ->update(['status' => Product::STATUS_CANCELLED]);

                $product = $this->productModel->create([
                    'sku' => $sku,
                    'marketplace_id' => $marketplaceId,
                    'price' => $price,
                    'status' => Product::STATUS_PENDING,
                ]);
                Event::dispatch(new ProductPriceChangePending($product));
                return $product;
            });
        } catch (\Throwable $e) {
            throw new ProductPriceChangeException('Could not initialize product price change: ' . $e->getMessage(), 0, $e);
        }
    }
}
