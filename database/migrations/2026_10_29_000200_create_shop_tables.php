<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->string('key', 60)->primary();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('label', 40)->nullable();
            $table->string('recipient_name');
            $table->string('phone', 20);
            $table->text('address_line');
            $table->string('district');
            $table->string('city');
            $table->string('province');
            $table->string('postal_code', 10);
            // Kode area dari API ongkir, bila nanti dipakai
            $table->string('area_code')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
        });

        // Hadiah otomatis (GWP)
        Schema::create('gift_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('gift_variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->unsignedInteger('min_subtotal');
            // false sampai pemilik toko memastikan produk dan hadiahnya
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });

        // Kosong = berlaku untuk semua produk
        Schema::create('gift_rule_product', function (Blueprint $table) {
            $table->foreignId('gift_rule_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->primary(['gift_rule_id', 'product_id']);
        });

        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained()->cascadeOnDelete();
            $table->string('session_id')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cart_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('quantity');
            $table->timestamps();

            $table->unique(['cart_id', 'product_variant_id']);
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->foreignId('user_id')->constrained();
            // Salinan alamat saat pesanan dibuat
            $table->string('recipient_name');
            $table->string('phone', 20);
            $table->text('address_line');
            $table->string('district');
            $table->string('city');
            $table->string('province');
            $table->string('postal_code', 10);
            $table->unsignedInteger('subtotal');
            $table->unsignedInteger('packaging_fee')->default(0);
            $table->unsignedInteger('shipping_cost')->default(0);
            $table->unsignedInteger('total');
            $table->unsignedInteger('total_weight_grams')->default(0);
            $table->string('status', 30)->index();
            $table->timestamp('payment_due_at')->nullable();
            $table->text('customer_note')->nullable();
            $table->timestamps();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained()->nullOnDelete();
            // Salinan nama, aroma, ukuran, harga saat dibeli
            $table->string('sku', 40);
            $table->string('product_name');
            $table->string('aroma_name');
            $table->string('size_label', 20)->nullable();
            $table->unsignedInteger('price');
            $table->unsignedSmallInteger('quantity');
            $table->unsignedSmallInteger('weight_grams');
            $table->boolean('is_gift')->default(false);
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('xendit_invoice_id')->nullable()->unique();
            $table->string('invoice_url')->nullable();
            $table->string('method', 40)->nullable();
            $table->unsignedInteger('amount');
            $table->string('status', 20);
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('courier', 40);
            $table->string('service', 60)->nullable();
            $table->unsignedInteger('cost');
            $table->string('tracking_number', 60)->nullable();
            $table->string('tracking_status', 60)->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamps();
        });

        // Jejak setiap perubahan stok
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')->constrained()->cascadeOnDelete();
            $table->integer('change');
            $table->unsignedInteger('stock_after');
            $table->string('reason', 30);
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('shipments');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('cart_items');
        Schema::dropIfExists('carts');
        Schema::dropIfExists('gift_rule_product');
        Schema::dropIfExists('gift_rules');
        Schema::dropIfExists('addresses');
        Schema::dropIfExists('settings');
    }
};
