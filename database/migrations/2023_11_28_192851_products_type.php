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
        Schema::create('products_type', function (Blueprint $table) {
            $table->id();
            $table->string('name_en');
            $table->string('name_ar');
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name_en');
            $table->string('name_ar');
            $table->float('price');
            $table->integer('quantity');
            $table->float('discounted_price')->nullable();
            $table->text('description_en');
            $table->text('description_ar');
            $table->text('how_to_use_en');
            $table->text('how_to_use_ar');
            $table->text('ingredients_en');
            $table->text('ingredients_ar');
            $table->unsignedBigInteger('type_id');
            $table->foreign('type_id')->references('id')->on('products_type');
            $table->timestamps();
        });
        Schema::create('products_images', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->foreign('product_id')->references('id')->on('products');
            $table->string('image_url');
            $table->timestamps();
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
