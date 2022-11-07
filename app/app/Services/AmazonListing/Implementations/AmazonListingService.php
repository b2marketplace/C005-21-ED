<?php

namespace App\Services\AmazonListing\Implementations;

use App\Services\AmazonListing\Contracts\AmazonListingServiceInterface;
use App\Services\AmazonListing\Exceptions\AmazonListingException;
use Illuminate\Support\Facades\Http;

class AmazonListingService implements AmazonListingServiceInterface
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
     * @throws AmazonListingException
     */
    public function patchListingItem(string $sellerId, string $sku, string $marketplaceId, string $productType, array $patches)
    {
        $url = "/listings/2021-08-01/items/{$sellerId}/{$sku}?marketplaceIds={$marketplaceId}";
        $body = [
            'productType' => $productType,
            'patches' => $patches,
        ];

        try {
            $response = Http::patch($url, $body);
            if (!$response->successful()) {
                throw new AmazonListingException('Amazon Listing PATCH failed: ' . $response->body());
            }
            return $response->json();
        } catch (\Throwable $e) {
            throw new AmazonListingException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
