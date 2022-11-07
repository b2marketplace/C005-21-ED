<?php

namespace App\Jobs\ProductPriceChange;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PerformPriceChange implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $product;

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
     * @param \App\Services\AmazonListing\Contracts\AmazonListingServiceInterface $listingService
     * @param \App\Services\AmazonSpApiCredentials\Contracts\AmazonSpApiCredentialsServiceInterface $credentialsService
     * @return void
     */
    public function handle(
        \App\Services\AmazonListing\Contracts\AmazonListingServiceInterface $listingService,
        \App\Services\AmazonSpApiCredentials\Contracts\AmazonSpApiCredentialsServiceInterface $credentialsService
    ) {
        $credentials = $credentialsService->getCredentials();
        $sellerId = $credentials['selling_partner_id'];
        $sku = $this->product->sku;
        $marketplaceId = $this->product->marketplace_id;
        $productType = $this->product->product_type;
        $price = $this->product->price;

        $patches = [
            [
                'op' => 'replace',
                'path' => '/attributes/purchasable_offer',
                'value' => [
                    [
                        'marketplace_id' => $marketplaceId,
                        'currency' => 'EUR',
                        'our_price' => [
                            [
                                'value_with_tax' => $price
                            ]
                        ]
                    ]
                ]
            ]
        ];

        try {
            $listingService->patchListingItem($sellerId, $sku, $marketplaceId, $productType, $patches);
            $this->product->status = Product::STATUS_COMPLETED;
        } catch (\Throwable $e) {
            $this->product->status = Product::STATUS_FAILED;
        }
        $this->product->save();
    }
}
