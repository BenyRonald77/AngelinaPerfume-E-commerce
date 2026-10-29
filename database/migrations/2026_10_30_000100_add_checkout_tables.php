<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabel ongkir manual per provinsi (driver ongkir awal; nanti bisa diganti API kurir)
        Schema::create('shipping_rates', function (Blueprint $table) {
            $table->id();
            $table->string('province');
            $table->string('courier', 60);
            $table->unsignedInteger('first_kg_price');
            $table->unsignedInteger('next_kg_price');
            $table->string('estimate', 40)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['province', 'courier']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('paid_at')->nullable()->after('payment_due_at');
            $table->timestamp('processed_at')->nullable()->after('paid_at');
            $table->timestamp('shipped_at')->nullable()->after('processed_at');
            $table->timestamp('completed_at')->nullable()->after('shipped_at');
            $table->timestamp('cancelled_at')->nullable()->after('completed_at');
            $table->string('cancel_reason')->nullable()->after('cancelled_at');
            $table->text('admin_note')->nullable()->after('customer_note');
        });

        Schema::table('payments', function (Blueprint $table) {
            // Catatan admin bila pembayaran ditandai lunas manual (Xendit belum aktif / transfer)
            $table->string('note')->nullable()->after('paid_at');
        });
    }

    public function down(): void
    {
        Schema::table('payments', fn (Blueprint $table) => $table->dropColumn('note'));
        Schema::table('orders', fn (Blueprint $table) => $table->dropColumn([
            'paid_at', 'processed_at', 'shipped_at', 'completed_at', 'cancelled_at', 'cancel_reason', 'admin_note',
        ]));
        Schema::dropIfExists('shipping_rates');
    }
};
