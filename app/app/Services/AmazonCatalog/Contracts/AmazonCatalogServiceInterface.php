<?php

namespace App\Services\AmazonCatalog\Contracts;

interface AmazonCatalogServiceInterface
{
    /**
     * Get product types for a SKU from Amazon Catalog API.
     *
     * @param string $sku
     * @param string $marketplaceId
     * @return array
     * @throws \App\Services\AmazonCatalog\Exceptions\AmazonCatalogException
     */
    public function getProductTypesBySku(string $sku, string $marketplaceId): array;
}
