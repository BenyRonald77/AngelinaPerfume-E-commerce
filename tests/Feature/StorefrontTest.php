<?php

namespace Tests\Feature;

use App\Livewire\Catalog;
use App\Models\Category;
use App\Models\GiftRule;
use App\Models\Product;
use App\Models\ProductAroma;
use App\Models\ProductVariant;
use App\Models\Size;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Once;
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
        $this->get('/')->assertOk()->assertSee('Pilih wangi favoritmu sesuai ciri khasmu.');
        $this->get('/produk')->assertOk()->assertSee('Belum ada produk yang cocok');
    }

    public function test_announcement_bar_shows_only_deliverable_gifts(): void
    {
        $gift = $this->product('Mystery Gift', Category::HADIAH, [['Acak', 2, 0, 5]])->variants()->first();
        $rule = GiftRule::create(['name' => 'Free decant (min. belanja Rp100.000)', 'gift_variant_id' => $gift->id, 'min_subtotal' => 100000]);

        // Belum diaktifkan admin: tidak dijanjikan
        $this->get('/')->assertOk()->assertDontSee('dapat free decant')->assertSee('Toko Shopee sejak 2023');

        // Promo::giftRules() memakai once() per proses; di tes beberapa permintaan berbagi satu proses
        $rule->update(['is_active' => true]);
        Once::flush();
        $this->get('/')->assertSee('Belanja min. Rp100.000 dapat free decant');

        // Stok hadiah habis: checkout tidak menambahkannya, bar juga berhenti menjanjikan
        $gift->update(['stock' => 0]);
        Once::flush();
        $this->get('/produk')->assertDontSee('dapat free decant');
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
