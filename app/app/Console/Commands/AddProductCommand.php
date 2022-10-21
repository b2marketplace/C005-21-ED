<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ProductPriceChange\Contracts\InitializeProductPriceChangeServiceInterface;
use App\Services\ProductPriceChange\Exceptions\ProductPriceChangeException;

class AddProductCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'product:add {sku} {price} {marketplace_id= A1RKKUPIHCS9HS}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add a new product with SKU, price and optional marketplace_id (default: A1RKKUPIHCS9HS for Spain)';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(InitializeProductPriceChangeServiceInterface $service)
    {
        $sku = $this->argument('sku');
        $price = $this->argument('price');
        $marketplaceId = trim($this->argument('marketplace_id'));

        if (!is_numeric($price) || $price <= 0) {
            $this->error('The price must be a number greater than 0.');
            return Command::FAILURE;
        }

        try {
            $product = $service->initialize($sku, (float)$price, $marketplaceId);
            $this->info("Product with SKU '{$product->sku}', price '{$product->price}' and marketplace_id '{$product->marketplace_id}' created successfully and pending price change.");
            return Command::SUCCESS;
        } catch (ProductPriceChangeException $e) {
            $this->error('Error: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
