<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Aturan hadiah bisa dibatasi per jenis produk: yang dihitung hanya subtotal produk jenis itu.
 * Permintaan klien: belanja DECANT min. Rp100.000 dapat hadiah random (decant, gantungan, atau item lain),
 * full size tidak ikut dihitung, satu hadiah per pesanan (tidak berlaku kelipatan).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gift_rules', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->after('min_subtotal')->constrained()->nullOnDelete();
            $table->string('description', 160)->nullable()->after('name');
        });

        // Aturan bawaan dari impor Shopee (hadiahnya SKU "Mystery Gift / Hadiah random")
        $decant = DB::table('categories')->where('slug', 'decant')->value('id');
        if ($decant) {
            DB::table('gift_rules')
                ->where('name', 'Free decant (min. belanja Rp100.000)')
                ->update([
                    'name' => 'Hadiah random',
                    'description' => 'Bisa decant, gantungan, atau item lain. 1 hadiah per pesanan.',
                    'category_id' => $decant,
                ]);
        }
    }

    public function down(): void
    {
        DB::table('gift_rules')->where('name', 'Hadiah random')->update(['name' => 'Free decant (min. belanja Rp100.000)']);

        Schema::table('gift_rules', function (Blueprint $table) {
            $table->dropConstrainedForeignId('category_id');
            $table->dropColumn('description');
        });
    }
};
