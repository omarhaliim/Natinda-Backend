<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->String('guest_id')->nullable();
            $table->unsignedBigInteger('shipping_address_id')->nullable();
            $table->float('subtotal');
            $table->integer('shipping_fees')->nullable();
            $table->integer('tax')->default(0);
            $table->unsignedBigInteger('promocode_id')->nullable();
            $table->float('promocode_price')->nullable();
            $table->integer('points')->nullable();
            $table->float('points_price')->nullable();
            $table->float('total_price')->nullable();
            $table->timestamps();
            $table->foreign('promocode_id')->references('id')->on('promocodes')->nullable();
            $table->foreign('shipping_address_id')->references('id')->on('shipping_address');
            $table->foreign('user_id')->references('id')->on('users');
        });

        Schema::create('cart_products', function (Blueprint $table) {
            $table->unsignedBigInteger('cart_id');
            $table->unsignedBigInteger('product_id');
            $table->integer('quantity');
            $table->primary(['cart_id', 'product_id']);
            $table->foreign('cart_id')->references('id')->on('carts');
            $table->foreign('product_id')->references('id')->on('products');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
