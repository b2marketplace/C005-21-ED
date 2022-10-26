<?php

namespace App\Services\AmazonCatalog\Contracts;

interface AmazonCatalogServiceInterface
{
    /**
     * Get the product type for a SKU from Amazon Catalog API.
     *
     * @param string $sku
     * @param string $marketplaceId
     * @return string
     * @throws \App\Services\AmazonCatalog\Exceptions\AmazonCatalogException
     * @throws \App\Services\AmazonCatalog\Exceptions\AmazonCatalogProductTypeNotFoundException
     * @throws \App\Services\AmazonCatalog\Exceptions\AmazonCatalogThrottledException
     * @throws \App\Services\AmazonCatalog\Exceptions\AmazonCatalogWaitLockException
     * @throws \App\Services\AmazonCatalog\Exceptions\AmazonCatalogUnauthorizedException
     */
    public function getProductTypeBySku(string $sku, string $marketplaceId): string;
}
