<?php

namespace App\Console\Commands;

use App\Services\ShopeeImporter;
use Illuminate\Console\Command;

class ImportShopee extends Command
{
    protected $signature = 'toko:impor-shopee
        {--tanpa-foto : Impor data saja, foto tidak diubah ke WebP}
        {--timpa : Timpa nama, merek, dan deskripsi produk yang sudah ada}
        {--folder=* : Hanya folder tertentu di shopee-media}';

    protected $description = 'Impor produk, aroma, SKU, harga, stok, dan foto dari ekspor Shopee (shopee-media)';

    public function handle(ShopeeImporter $importer): int
    {
        $path = config('toko.shopee_media_path');
        $path = preg_match('/^([A-Za-z]:|[\\\\\/])/', $path) ? $path : base_path($path);

        if (! is_dir($path)) {
            $this->error("Folder {$path} tidak ditemukan. Atur SHOPEE_MEDIA_PATH di .env.");

            return self::FAILURE;
        }

        $this->info("Membaca {$path}");
        $bar = $this->output->createProgressBar();
        $bar->setFormat(' %current% folder [%bar%] %elapsed:6s%  %message%');
        $bar->setMessage('');
        $bar->start();

        $report = $importer->import(
            $path,
            withImages: ! $this->option('tanpa-foto'),
            overwrite: (bool) $this->option('timpa'),
            onlyFolders: $this->option('folder'),
            onProgress: function (string $folder) use ($bar) {
                $bar->setMessage(mb_strimwidth($folder, 0, 50, '…'));
                $bar->advance();
            },
        );

        $bar->finish();
        $this->newLine(2);

        $this->table(['', 'Jumlah'], [
            ['Produk baru', $report->productsCreated],
            ['Produk diperbarui', $report->productsUpdated],
            ['Aroma baru', $report->aromas],
            ['SKU diproses', $report->variants],
            ['SKU dinonaktifkan (tidak ada di ekspor)', $report->variantsDeactivated],
            ['Foto diubah ke WebP', $report->imagesConverted],
            ['Foto dilewati (sudah ada)', $report->imagesSkipped],
        ]);

        $log = storage_path('logs/impor-shopee-'.now()->format('Ymd-His').'.txt');
        file_put_contents($log, implode(PHP_EOL, [
            'PERLU DICEK ('.count($report->warnings).')', ...$report->warnings, '',
            'KETERANGAN ('.count($report->notes).')', ...$report->notes,
        ]));

        if ($report->warnings !== []) {
            $this->warn(count($report->warnings).' hal perlu dicek:');
            foreach (array_slice($report->warnings, 0, 15) as $warning) {
                $this->line("  - {$warning}");
            }
        }

        $this->line("Laporan lengkap: {$log}");

        return self::SUCCESS;
    }
}
