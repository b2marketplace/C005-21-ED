<?php

namespace App\Services\AmazonCatalog\Implementations;

use App\Services\AmazonCatalog\Contracts\AmazonCatalogServiceInterface;
use App\Services\AmazonCatalog\Exceptions\AmazonCatalogException;
use App\Services\AmazonCatalog\Exceptions\AmazonCatalogThrottledException;
use App\Services\AmazonCatalog\Exceptions\AmazonCatalogUnauthorizedException;
use App\Services\AmazonCatalog\Exceptions\AmazonCatalogProductTypeNotFoundException;
use App\Services\AmazonSpApiCredentials\Contracts\AmazonSpApiCredentialsServiceInterface;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;
use App\Services\AmazonCatalog\Exceptions\AmazonCatalogWaitLockException;

class AmazonCatalogService implements AmazonCatalogServiceInterface
{
    protected $credentialsService;
    protected $httpClient;

    public function __construct(AmazonSpApiCredentialsServiceInterface $credentialsService, Client $httpClient = null)
    {
        $this->credentialsService = $credentialsService;
        $this->httpClient = $httpClient ?: new Client();
    }

    /**
     * {@inheritdoc}
     */
    public function getProductTypeBySku(string $sku, string $marketplaceId): string
    {
        $lock = Cache::lock('get_product_type', 2);
        if (!$lock->get()) {
            throw new AmazonCatalogWaitLockException('Could not obtain lock for get_product_type');
        }
        try {
            $credentials = $this->credentialsService->getCredentials();
            $accessToken = $credentials['access_token'];
            $endpoint = $credentials['endpoint'] ?? 'https://sellingpartnerapi-eu.amazon.com';

            $response = $this->httpClient->request('POST', "$endpoint/catalog/2022-04-01/items", [
                'headers' => [
                    'x-amz-access-token' => $accessToken,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'identifiers' => [$sku],
                    'marketplaceIds' => [$marketplaceId],
                    'includedData' => ['productTypes'],
                    'pageSize' => 1,
                ],
                'http_errors' => false,
            ]);

            $status = $response->getStatusCode();
            if ($status === 429) {
                throw new AmazonCatalogThrottledException('Amazon SP-API request throttled (HTTP 429)');
            }
            if ($status === 401 || $status === 403) {
                throw new AmazonCatalogUnauthorizedException('Amazon SP-API unauthorized (HTTP ' . $status . ')');
            }
            if ($status < 200 || $status >= 300) {
                throw new AmazonCatalogException('Amazon SP-API error: HTTP ' . $status . ' - ' . $response->getBody());
            }

            $data = json_decode($response->getBody(), true);
            $productType = $data['items'][0]['productTypes'][0]['productType'] ?? null;
            if (empty($productType)) {
                throw new AmazonCatalogProductTypeNotFoundException('No productType found for SKU: ' . $sku);
            }
            return $productType;
        } catch (AmazonCatalogThrottledException | AmazonCatalogUnauthorizedException | AmazonCatalogWaitLockException | AmazonCatalogProductTypeNotFoundException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new AmazonCatalogException('Error getting product type: ' . $e->getMessage(), 0, $e);
        } finally {
            optional($lock)->release();
        }
    }
}
