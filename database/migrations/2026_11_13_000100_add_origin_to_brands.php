<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            // Merek baru dari impor masuk "lainnya" sampai admin menentukan asalnya
            $table->string('origin', 20)->default('lainnya')->index()->after('slug');
        });

        // Pengelompokan awal katalog November 2026 (bisa diubah admin di menu Merek):
        // Timur Tengah/UAE: AFNAN, ZIMAYA (lini AFNAN), RASASI, Fragrance World. MINISO bukan merek lokal.
        // Merek lain di katalog adalah brand parfum Indonesia.
        DB::table('brands')->whereIn('slug', ['afnan', 'zimaya', 'rasasi', 'fragrance-world'])->update(['origin' => 'dubai']);
        DB::table('brands')->whereNotIn('slug', ['afnan', 'zimaya', 'rasasi', 'fragrance-world', 'miniso'])->update(['origin' => 'lokal']);
    }

    public function down(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            $table->dropIndex(['origin']);
            $table->dropColumn('origin');
        });
    }
};
