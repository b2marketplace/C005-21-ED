<?php

namespace App\Services\ProductPriceChange\Implementations;

use App\Models\Product;
use App\Services\ProductPriceChange\Contracts\SetProductTypeServiceInterface;
use App\Services\ProductPriceChange\Exceptions\SetProductTypeException;

class SetProductTypeService implements SetProductTypeServiceInterface
{
    /**
     * {@inheritdoc}
     */
    public function setProductType(Product $product, ?string $productType): void
    {
        try {
            $product->product_type = $productType;
            $product->status = Product::STATUS_IN_PROGRESS;
            $product->save();
        } catch (\Throwable $e) {
            throw new SetProductTypeException('Could not set product type: ' . $e->getMessage(), 0, $e);
        }
    }
}
