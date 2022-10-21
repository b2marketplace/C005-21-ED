<?php

namespace App\Services\ProductPriceChange\Contracts;

use App\Models\Product;

interface InitializeProductPriceChangeServiceInterface
{
    /**
     * Initialize a product price change process.
     *
     * @param string $sku
     * @param float $price
     * @param string $marketplaceId
     * @return Product
     * @throws \App\Services\ProductPriceChange\Exceptions\ProductPriceChangeException
     */
    public function initialize(string $sku, float $price, string $marketplaceId): Product;
}
