<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('logo')->nullable();
            $table->timestamps();
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // Daftar ukuran tetap (aturan bisnis 4)
        Schema::create('sizes', function (Blueprint $table) {
            $table->id();
            $table->string('label', 20);
            $table->decimal('ml', 5, 1)->unique();
            $table->string('bottle_type', 20);
            $table->unsignedTinyInteger('sort_order')->default(0);
        });

        $sizes = [
            ['0,5 ml', 0.5, 'vial_oles'], ['1 ml', 1, 'vial_oles'],
            ['2 ml', 2, 'spray'], ['3 ml', 3, 'spray'], ['5 ml', 5, 'spray'], ['10 ml', 10, 'spray'],
            ['15 ml', 15, 'travel'],
            ['37 ml', 37, 'botol_asli'], ['50 ml', 50, 'botol_asli'], ['100 ml', 100, 'botol_asli'],
        ];
        foreach ($sizes as $i => [$label, $ml, $type]) {
            DB::table('sizes')->insert(['label' => $label, 'ml' => $ml, 'bottle_type' => $type, 'sort_order' => $i + 1]);
        }

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            // Nomor tetap untuk kode SKU AP-<nomor>-..., tidak berubah walau impor diulang
            $table->unsignedSmallInteger('number')->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('category_id')->constrained();
            $table->string('concentration', 40)->nullable();
            $table->string('gender', 10)->nullable();
            // Deskripsi yang tampil di halaman produk
            $table->longText('description')->nullable();
            // Deskripsi asli Shopee, disimpan untuk rujukan admin
            $table->longText('shopee_description')->nullable();
            $table->boolean('is_visible')->default(true)->index();
            // Botol terjual di Shopee Juli–Oktober 2026 (produk-terlaris.csv), untuk bagian Terlaris
            $table->unsignedInteger('sold_count')->default(0)->index();
            $table->string('source_folder')->nullable()->unique();
            $table->timestamps();
        });

        // Satu produk bisa berasal dari beberapa listing Shopee (PART 1/2/3)
        Schema::create('product_shopee_listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('shopee_product_id', 20)->unique();
            $table->string('parent_sku', 40)->nullable();
            $table->string('original_name');
            $table->timestamps();
        });

        Schema::create('product_aromas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('number');
            $table->string('name');
            $table->string('image')->nullable();
            $table->unsignedSmallInteger('image_width')->nullable();
            $table->unsignedSmallInteger('image_height')->nullable();
            $table->string('notes_top')->nullable();
            $table->string('notes_middle')->nullable();
            $table->string('notes_base')->nullable();
            // Untuk sumber yang hanya menulis "Notes" tanpa top/middle/base
            $table->string('notes_general')->nullable();
            $table->string('note', 60)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['product_id', 'number']);
        });

        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_aroma_id')->constrained()->cascadeOnDelete();
            $table->foreignId('size_id')->nullable()->constrained();
            $table->string('sku', 40)->unique();
            $table->string('shopee_variation_id', 20)->nullable()->unique();
            $table->string('shopee_variation_name')->nullable();
            // Rupiah, angka bulat
            $table->unsignedInteger('price');
            $table->unsignedSmallInteger('weight_grams');
            // unsigned: stok tidak boleh minus (aturan bisnis 8)
            $table->unsignedInteger('stock')->default(0);
            $table->unsignedSmallInteger('max_per_order')->nullable();
            $table->string('note', 60)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['product_id', 'is_active']);
        });

        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            // Path tanpa akhiran ukuran; file: <path>-800.webp dan <path>-1600.webp
            $table->string('path');
            $table->unsignedSmallInteger('width')->nullable();
            $table->unsignedSmallInteger('height')->nullable();
            $table->string('alt')->nullable();
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_images');
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('product_aromas');
        Schema::dropIfExists('product_shopee_listings');
        Schema::dropIfExists('products');
        Schema::dropIfExists('sizes');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('brands');
    }
};
