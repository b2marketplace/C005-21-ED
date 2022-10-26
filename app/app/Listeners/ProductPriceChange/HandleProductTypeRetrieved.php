<?php

namespace App\Listeners\ProductTypeRetrieved;

use App\Events\ProductTypeRetrieved;
use App\Jobs\ProductPriceChange\PerformPriceChange;

class HandleProductTypeRetrieved
{
    /**
     * Handle the event.
     *
     * @param  ProductTypeRetrieved  $event
     * @return void
     */
    public function handle(ProductTypeRetrieved $event)
    {
        PerformPriceChange::dispatch($event->product);
    }
}
