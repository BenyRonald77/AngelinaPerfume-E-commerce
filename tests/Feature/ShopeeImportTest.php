<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Services\ShopeeImporter;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

class ShopeeImportTest extends TestCase
{
    use RefreshDatabase;

    private string $base;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->base = sys_get_temp_dir().'/shopee-media-test-'.uniqid();
        $this->makeFixture();
    }

    protected function tearDown(): void
    {
        (new Filesystem)->deleteDirectory($this->base);

        parent::tearDown();
    }

    public function test_import_builds_catalog_from_export(): void
    {
        $report = app(ShopeeImporter::class)->import($this->base);

        $this->assertSame([], $report->warnings);
        // Dus/bubble wrap bukan produk; PART 1 dan 2 menjadi satu produk
        $this->assertSame(3, Product::count());
        $this->assertSame('1700', Setting::get('packaging_fee'));

        $zataru = Product::where('name', 'Zataru Signature')->firstOrFail();
        $this->assertSame('Zataru', $zataru->brand->name);
        $this->assertSame(['Black Onyx', 'Xoxo, Rosy'], $zataru->aromas->pluck('name')->all());
        $this->assertTrue(ProductVariant::where('sku', 'AP-0003-01-0.5ML')->exists());
        $this->assertTrue(ProductVariant::where('sku', 'AP-0003-01-2ML')->exists());
        $this->assertNull($zataru->description, 'Deskripsi decant (aturan toko) tidak tampil per produk');

        // Foto diubah ke WebP 800 dan 1600 px
        Storage::disk('public')->assertExists('produk/0003/00-sampul-800.webp');
        Storage::disk('public')->assertExists('produk/0003/00-sampul-1600.webp');
        $this->assertSame('produk/0003/varian-01-black-onyx', $zataru->aromas->first()->image);

        $penthouse = Product::where('name', 'MYKONOS Penthouse')->firstOrFail();
        $aroma = $penthouse->aromas->sole();
        $this->assertSame('Penthouse', $aroma->name);
        $this->assertSame('Sparkling Blackcurrant, Aldehydes', $aroma->notes_top);
        $variant = $penthouse->variants->sole();
        $this->assertSame('AP-0002-01-100ML', $variant->sku);
        $this->assertSame(1, $variant->max_per_order);
        $this->assertSame(499000, $variant->price);

        $merged = Product::where('name', 'MYKONOS Decant')->firstOrFail();
        $this->assertSame(2, $merged->shopeeListings()->count());
        $this->assertSame(2, $merged->variants()->count());
        // Notes yang diketik dari foto varian (notes-aroma.php)
        $this->assertSame('Cherry, Amber', $merged->aromas()->where('name', 'Crimson')->value('notes_general'));
    }

    public function test_reimport_keeps_codes_and_logs_stock_changes(): void
    {
        $importer = app(ShopeeImporter::class);
        $importer->import($this->base, withImages: false);
        $sku = ProductVariant::where('shopee_variation_id', '9001')->value('sku');

        // Ekspor baru: stok Black Onyx 0,5 ml turun dari 30 menjadi 27
        $csv = file_get_contents("{$this->base}/harga-stok-semua.csv");
        file_put_contents("{$this->base}/harga-stok-semua.csv", str_replace('9001,"[ DECANT / SHARE ] Zataru Signature All Variant | Size 0,5mL, 2mL",Black Onyx,"0,5mL (vial oles)",,9000,30', '9001,"[ DECANT / SHARE ] Zataru Signature All Variant | Size 0,5mL, 2mL",Black Onyx,"0,5mL (vial oles)",,9000,27', $csv));

        $report = $importer->import($this->base, withImages: false);

        $variant = ProductVariant::where('shopee_variation_id', '9001')->firstOrFail();
        $this->assertSame(0, $report->productsCreated);
        $this->assertSame($sku, $variant->sku);
        $this->assertSame(27, $variant->stock);
        $this->assertSame([30, -3], $variant->stockMovements()->orderBy('id')->pluck('change')->all());
    }

    private function makeFixture(): void
    {
        // Data impor contoh, terpisah dari database/data yang dipakai impor sungguhan
        mkdir("{$this->base}/data", 0777, true);
        config(['toko.import_data_path' => "{$this->base}/data"]);
        file_put_contents("{$this->base}/data/merek.php", "<?php return ['MYKONOS' => [], 'Zataru' => []];");
        file_put_contents("{$this->base}/data/koreksi-shopee.php", "<?php return ['produk' => ['decant-mykonos-semua-varian' => ['name' => 'MYKONOS Decant', 'brand' => 'MYKONOS']], 'aroma' => []];");
        file_put_contents("{$this->base}/data/notes-aroma.php", "<?php return ['decant-mykonos-semua-varian' => ['Crimson' => ['general' => 'Cherry, Amber']]];");

        $zataru = 'zataru-signature-58002985927';
        foreach ([$zataru, 'mykonos-penthouse-100ml-1', 'decant-mykonos-semua-varian', 'extra-dus-bubble-wrap-2'] as $dir) {
            mkdir("{$this->base}/{$dir}", 0777, true);
        }
        mkdir("{$this->base}/ekspor");

        $zataruName = '"[ DECANT / SHARE ] Zataru Signature All Variant | Size 0,5mL, 2mL"';
        $penthouseName = '[ FULL SIZE ] MYKONOS PENTHOUSE 100ML Extrait De Parfum - Angelia Perfume';
        file_put_contents("{$this->base}/harga-stok-semua.csv", implode("\n", [
            "\u{FEFF}folder,kode_produk,kode_variasi,nama_produk,aroma,ukuran,sku,harga,stok,berat_gram",
            "{$zataru},58002985927,9001,{$zataruName},Black Onyx,\"0,5mL (vial oles)\",,9000,30,60",
            "{$zataru},58002985927,9002,{$zataruName},Black Onyx,2mL (spray),,20000,29,60",
            "{$zataru},58002985927,9003,{$zataruName},\"Xoxo, Rosy\",2mL (spray),,20000,0,60",
            "mykonos-penthouse-100ml-1,111,9101,{$penthouseName},,Full Size 100ML,,499000,0,700",
            'decant-mykonos-semua-varian,201,9201,"[ DECANT / SHARE ] MYKONOS Best Seller - PART 1 | Size 1mL",Crimson,1mL (vial),,15000,5,30',
            'decant-mykonos-semua-varian,202,9202,"[ DECANT / SHARE ] MYKONOS Non-Best Seller - PART 2 | Size 1mL",Sansa,1mL (vial),,12000,5,30',
            'extra-dus-bubble-wrap-2,300,9301,EXTRA DUS / BUBBLE WRAP (Optional),,Dus/Bubble wrap,,1700,0,20',
        ])."\n");

        file_put_contents("{$this->base}/{$zataru}/info.txt", implode("\n", [
            'Kode Produk: 58002985927',
            'SKU Induk: AP-DL10',
            'Nama Produk: [ DECANT / SHARE ] Zataru Signature All Variant | Size 0,5mL, 2mL',
            '',
            'Foto:',
            '  00-sampul: https://cf.shopee.co.id/file/a',
            '  varian-01-black-onyx: https://cf.shopee.co.id/file/b',
            '',
        ]));
        file_put_contents("{$this->base}/{$zataru}/deskripsi.txt", "Welcome to Angelia Perfume!\nMEMBELI=SETUJU");
        $this->jpeg("{$this->base}/{$zataru}/00-sampul.jpg");
        $this->jpeg("{$this->base}/{$zataru}/varian-01-black-onyx.jpg");

        file_put_contents("{$this->base}/mykonos-penthouse-100ml-1/deskripsi.txt", implode("\n", [
            'MYKONOS Penthouse', '', 'Top- Sparkling Blackcurrant, Aldehydes', 'Middle - White Tea', 'Base - Amber', '***', '[Ketentuan Pengiriman]',
        ]));

        file_put_contents("{$this->base}/decant-mykonos-semua-varian/info.txt", implode("\n", [
            'Gabungan 3 produk Shopee (PART):',
            'Kode Produk: 201', 'SKU Induk: AP-DL7A', 'Nama Produk: [ DECANT / SHARE ] MYKONOS Best Seller - PART 1 | Size 1mL', '',
            'Kode Produk: 202', 'SKU Induk: AP-DL7B', 'Nama Produk: [ DECANT / SHARE ] MYKONOS Non-Best Seller - PART 2 | Size 1mL', '',
        ]));

        $this->salesXlsx("{$this->base}/ekspor/mass_update_sales_info_1_20261028.xlsx", ['9101' => 1]);
    }

    private function jpeg(string $path): void
    {
        $image = imagecreatetruecolor(40, 40);
        imagefill($image, 0, 0, imagecolorallocate($image, 249, 182, 186));
        imagejpeg($image, $path);
        imagedestroy($image);
    }

    /** xlsx minimal dengan kolom yang dibaca SalesInfoReader */
    private function salesXlsx(string $path, array $max): void
    {
        $cell = fn (string $ref, string $text) => "<c r=\"{$ref}\" t=\"inlineStr\"><is><t>{$text}</t></is></c>";
        $rows = ['<row r="1">'.$cell('C1', 'Kode Variasi').$cell('K1', 'Maks. Jumlah Pembelian').'</row>'];
        $r = 2;
        foreach ($max as $variation => $limit) {
            $rows[] = "<row r=\"{$r}\">".$cell("C{$r}", $variation)."<c r=\"K{$r}\"><v>{$limit}</v></c></row>";
            $r++;
        }

        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::CREATE);
        $zip->addFromString('xl/worksheets/sheet1.xml', '<?xml version="1.0"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>'.implode('', $rows).'</sheetData></worksheet>');
        $zip->close();
    }
}
