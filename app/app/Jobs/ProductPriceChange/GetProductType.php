<?php

namespace App\Jobs\ProductPriceChange;

use App\Models\Product;
use App\Services\AmazonCatalog\Contracts\AmazonCatalogServiceInterface;
use App\Services\ProductPriceChange\Contracts\SetProductTypeServiceInterface;
use App\Services\AmazonCatalog\Exceptions\AmazonCatalogThrottledException;
use App\Services\AmazonCatalog\Exceptions\AmazonCatalogUnauthorizedException;
use App\Services\AmazonCatalog\Exceptions\AmazonCatalogWaitLockException;
use App\Events\AmazonSpApiCredentialsExpired;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\Middleware\ThrottlesExceptions;

class GetProductType implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $product;
    public $marketplaceId;

    /**
     * Create a new job instance.
     *
     * @param Product $product
     * @return void
     */
    public function __construct(Product $product)
    {
        $this->product = $product;
    }

    /**
     * Execute the job.
     *
     * @param AmazonCatalogServiceInterface $catalogService
     * @param SetProductTypeServiceInterface $setProductTypeService
     * @return void
     */
    public function handle(AmazonCatalogServiceInterface $catalogService, SetProductTypeServiceInterface $setProductTypeService)
    {
        if ($this->product->status !== Product::STATUS_PENDING) {
            return;
        }
        try {
            $productTypes = $catalogService->getProductTypesBySku($this->product->sku, $this->product->marketplace_id);
            $setProductTypeService->setProductType($this->product, $productTypes[0]['productType'] ?? null);
        } catch (AmazonCatalogThrottledException | AmazonCatalogWaitLockException $e) {
            Log::warning('GetProductType throttled or lock: ' . $e->getMessage());
            $this->release(rand(60, 120));
        } catch (AmazonCatalogUnauthorizedException $e) {
            Log::error('GetProductType unauthorized: ' . $e->getMessage());
            event(new AmazonSpApiCredentialsExpired());
            $this->release(rand(300, 360));
        } catch (\Throwable $e) {
            Log::error('GetProductType error: ' . $e->getMessage());
            $this->product->status = Product::STATUS_FAILED;
            $this->product->save();
        }
    }

    /**
     * Determine the time at which the job should stop retrying.
     *
     * @return \DateTime
     */
    public function retryUntil()
    {
        return now()->addMinutes(1440);
    }

    public function middleware()
    {
        return [(new ThrottlesExceptions(10, 5))->backoff(5)];
    }
}
