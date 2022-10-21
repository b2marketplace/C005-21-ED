<?php

namespace App\Services\ProductPriceChange\Contracts;

use App\Models\Product;

interface SetProductTypeServiceInterface
{
    /**
     * Set the product_type for a product.
     *
     * @param Product $product
     * @param string|null $productType
     * @return void
     * @throws \App\Services\ProductPriceChange\Exceptions\SetProductTypeException
     */
    public function setProductType(Product $product, ?string $productType): void;
}
