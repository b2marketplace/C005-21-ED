<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('sku');
            $table->string('marketplace_id');
            $table->string('product_type')->nullable();
            $table->decimal('price', 10, 2);
            $table->unsignedTinyInteger('status')->default(0)->comment('0: pending, 1: in progress, 2: completed, 3: failed, 4: expired, 5: cancelled');

            $table->index(['marketplace_id', 'sku']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('products');
    }
};
