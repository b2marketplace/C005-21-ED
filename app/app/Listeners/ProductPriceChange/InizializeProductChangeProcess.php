<?php

namespace App\Listeners\ProductPriceChange;

use App\Events\ProductPriceChangePending;
use App\Jobs\ProductPriceChange\GetProductType;

class InizializeProductChangeProcess
{
    /**
     * Handle the event.
     *
     * @param  ProductPriceChangePending  $event
     * @return void
     */
    public function handle(ProductPriceChangePending $event)
    {
        // Dispatch job to get product type
        GetProductType::dispatch($event->product);
    }
}
