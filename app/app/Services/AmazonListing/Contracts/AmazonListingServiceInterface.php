<?php

namespace App\Services\AmazonListing\Contracts;

interface AmazonListingServiceInterface
{
    /**
     * Patch listing item on Amazon.
     *
     * @param string $sellerId
     * @param string $sku
     * @param string $marketplaceId
     * @param string $productType
     * @param array $patches
     * @return mixed
     * @throws \App\Services\AmazonListing\Exceptions\AmazonListingException
     */
    public function patchListingItem(string $sellerId, string $sku, string $marketplaceId, string $productType, array $patches);
}
