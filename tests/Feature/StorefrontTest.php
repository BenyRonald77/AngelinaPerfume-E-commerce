<?php

namespace Tests\Feature;

use App\Enums\BrandOrigin;
use App\Livewire\Catalog;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductAroma;
use App\Models\ProductVariant;
use App\Models\Size;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class StorefrontTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Category::ensureDefaults();
    }

    public function test_pages_open_with_empty_catalog(): void
    {
        $this->get('/')->assertOk()->assertSeeText('Pilih wangi favoritmu sesuai ciri khas mu.')->assertSee('videos/hero-parfum-pink.mp4')->assertSee('Jam operasional: Senin sampai Jumat 09.00–16.00 WIB');
        $this->get('/produk')->assertOk()->assertSee('Belum ada produk yang cocok');
    }

    public function test_privacy_policy_is_linked_from_footer(): void
    {
        $this->get('/')->assertOk()->assertSee(route('privasi'));
        $this->get('/kebijakan-privasi')->assertOk()->assertSee('Kebijakan Privasi')->assertSee('PT Jogja Creative Production');
    }

    public function test_home_and_catalog_show_real_products(): void
    {
        $zataru = $this->product('Zataru Signature', Category::DECANT, [
            ['Black Onyx', 0.5, 9000, 30, ['notes_top' => 'Green Apple, Bergamot']],
            ['Topaz', 2, 20000, 0],
        ]);

        $this->get('/')->assertOk()->assertSee('Zataru Signature')->assertSee('mulai')->assertSee('Rp9.000');

        Livewire::test(Catalog::class)
            ->assertSee('Zataru Signature')
            ->set('search', 'bergamot')
            ->assertSee('Zataru Signature')
            ->set('search', 'tidakada')
            ->assertSee('Belum ada produk yang cocok')
            ->call('resetFilters')
            ->set('jenis', Category::FULL_SIZE)
            ->assertDontSee('Zataru Signature');

        $this->assertSame('zataru-signature', $zataru->slug);
    }

    public function test_sold_out_products_hide_behind_availability_filter(): void
    {
        $this->product('MYKONOS Penthouse', Category::FULL_SIZE, [['Penthouse', 100, 499000, 0]]);

        Livewire::test(Catalog::class)
            ->assertDontSee('MYKONOS Penthouse')
            ->set('tersedia', false)
            ->assertSee('MYKONOS Penthouse')
            ->assertSee('Tanya stok via WhatsApp');
    }

    public function test_product_page_selects_variant_from_url(): void
    {
        $product = $this->product('Zataru Signature', Category::DECANT, [
            ['Black Onyx', 0.5, 9000, 30, ['notes_top' => 'Green Apple']],
            ['Amethyst', 2, 20000, 4],
        ]);

        $this->get('/produk/zataru-signature')
            ->assertOk()
            ->assertSee('Black Onyx · 0,5 ml')
            ->assertSee('Tersedia · 30 botol')
            ->assertSee('Green Apple');

        $sku = $product->variants()->whereHas('aroma', fn ($q) => $q->where('name', 'Amethyst'))->value('sku');
        $this->get("/produk/zataru-signature?varian={$sku}")
            ->assertOk()
            ->assertSee('Amethyst · 2 ml')
            ->assertSee('Stok menipis · 4 botol')
            ->assertSee('Rp20.000');
    }

    public function test_sold_out_variant_offers_whatsapp_with_placeholder(): void
    {
        config(['toko.whatsapp_number' => null]);
        $this->product('MYKONOS Penthouse', Category::FULL_SIZE, [['Penthouse', 100, 499000, 0]]);

        $this->get('/produk/mykonos-penthouse')
            ->assertOk()
            ->assertSee('Stok habis')
            ->assertSee('[NOMOR TOKO]');
    }

    public function test_hidden_and_gift_products_are_not_public(): void
    {
        $this->product('Mystery Gift', Category::HADIAH, [['Hadiah random', null, 99999, 10]]);
        $hidden = $this->product('Produk Disembunyikan', Category::DECANT, [['A', 1, 10000, 5]]);
        $hidden->update(['is_visible' => false]);

        $this->get('/produk/mystery-gift')->assertNotFound();
        $this->get('/produk/produk-disembunyikan')->assertNotFound();
        $this->get('/')->assertDontSee('Mystery Gift');
    }

    /**
     * @param  list<array{0: string, 1: ?float, 2: int, 3: int, 4?: array}>  $rows  aroma, ml, harga, stok, kolom aroma lain
     */
    public function test_collections_group_brands_by_origin(): void
    {
        $afnan = Brand::create(['name' => 'AFNAN', 'slug' => 'afnan', 'origin' => BrandOrigin::Dubai]);
        $mykonos = Brand::create(['name' => 'MYKONOS', 'slug' => 'mykonos', 'origin' => BrandOrigin::Lokal]);
        $this->product('AFNAN 9 PM', Category::FULL_SIZE, [['9 PM', 100, 350000, 3]])->update(['brand_id' => $afnan->id]);
        $this->product('MYKONOS Decant', Category::DECANT, [['Aphrodite', 2, 20000, 9]])->update(['brand_id' => $mykonos->id]);

        // Kelompok tanpa produk tetap tampil dengan "Segera hadir", tanpa tautan ke katalog kosong
        $this->get('/')->assertOk()
            ->assertSee('Parfum Timur Tengah')->assertSee('Brand parfum Indonesia')
            ->assertSee(route('produk.index', ['asal' => 'dubai']), false)
            ->assertSee('Rumah mode dunia')->assertSee('Segera hadir')
            ->assertDontSee(route('produk.index', ['asal' => 'designer']), false);

        // Halaman Merek: semua merek A-Z tanpa kelompok asal, logo bawaan, tautan ke katalog merek itu
        $this->get('/merek')->assertOk()
            ->assertSeeInOrder(['AFNAN', 'MYKONOS'])
            ->assertDontSee('Segera hadir')
            ->assertSee(asset('images/merek/afnan.webp'), false)
            ->assertSee(route('produk.index', ['merek' => ['afnan']]), false);

        // Merek tanpa logo bawaan tampil sebagai teks; unggahan admin mengalahkan logo bawaan
        $tanpaLogo = Brand::create(['name' => 'Merek Baru', 'slug' => 'merek-baru-tanpa-logo']);
        $this->assertNull($tanpaLogo->logoUrl());
        $afnan->update(['logo' => 'merek/afnan-baru.png']);
        $this->assertStringEndsWith('/storage/merek/afnan-baru.png', $afnan->logoUrl());

        Livewire::test(Catalog::class, ['asal' => 'dubai'])
            ->assertSee('AFNAN 9 PM')
            ->assertDontSee('MYKONOS Decant');
    }

    private function product(string $name, string $category, array $rows): Product
    {
        $product = Product::create([
            'number' => (int) Product::max('number') + 1,
            'name' => $name,
            'slug' => Str::slug($name),
            'category_id' => Category::where('slug', $category)->value('id'),
        ]);

        foreach ($rows as $i => $row) {
            $aroma = ProductAroma::create(['product_id' => $product->id, 'number' => $i + 1, 'name' => $row[0], 'sort_order' => $i + 1, ...($row[4] ?? [])]);
            $size = $row[1] !== null ? Size::where('ml', $row[1])->first() : null;
            ProductVariant::create([
                'product_id' => $product->id, 'product_aroma_id' => $aroma->id, 'size_id' => $size?->id,
                'sku' => ProductVariant::generateSku($product, $aroma, $size),
                'price' => $row[2], 'weight_grams' => 60, 'stock' => $row[3],
            ]);
        }

        return $product;
    }
}
