<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Filament\Resources\FlashSales\Pages\CreateFlashSale;
use App\Livewire\Checkout;
use App\Models\Category;
use App\Models\FlashSale;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductAroma;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Models\Size;
use App\Models\User;
use Carbon\CarbonImmutable;
use Filament\Facades\Filament;
use Filament\Forms\Components\Repeater;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class FlashSaleTest extends TestCase
{
    use RefreshDatabase;

    private ProductVariant $variant;

    protected function setUp(): void
    {
        parent::setUp();

        Category::ensureDefaults();
        Notification::fake();
        $this->withoutDefer();
        $this->travelTo(CarbonImmutable::parse('2026-11-16 10:00'));

        $product = Product::create([
            'number' => 1, 'name' => 'MYKONOS Travel Size', 'slug' => 'mykonos-travel-size', 'sold_count' => 50,
            'category_id' => Category::where('slug', Category::FULL_SIZE)->value('id'),
        ]);
        ProductImage::create(['product_id' => $product->id, 'path' => 'produk/0001/00-sampul', 'width' => 800, 'height' => 800, 'sort_order' => 0]);
        $aroma = ProductAroma::create(['product_id' => $product->id, 'number' => 1, 'name' => 'Penthouse']);
        $size = Size::where('ml', 15)->first();
        $this->variant = ProductVariant::create([
            'product_id' => $product->id, 'product_aroma_id' => $aroma->id, 'size_id' => $size?->id,
            'sku' => ProductVariant::generateSku($product, $aroma, $size), 'price' => 100000, 'weight_grams' => 80, 'stock' => 10,
        ]);

        ShippingRate::create(['province' => 'DI Yogyakarta', 'courier' => 'J&T EZ', 'first_kg_price' => 10000, 'next_kg_price' => 8000, 'estimate' => '1-2 hari']);
    }

    public function test_twin_dates_start_from_next_twin_date(): void
    {
        $dates = FlashSale::twinDates(3);

        $this->assertSame(['2026-12-12', '2027-01-01', '2027-02-02'], array_keys($dates));
        $this->assertSame('12.12 · Sabtu, 12 Desember 2026', $dates['2026-12-12']);
    }

    public function test_upcoming_sale_shows_countdown_but_price_stays_normal(): void
    {
        $sale = $this->sale('2026-12-12 00:00', '2026-12-12 23:59', 80000);

        $this->get('/')->assertOk()
            ->assertSee('Flash Sale 12.12')->assertSee('Mulai dalam')->assertSee('Segera dimulai')
            ->assertSee('Rp80.000')->assertSee('Rp100.000')->assertSee('-20%');

        // Belum mulai: harga yang dibayar masih normal, halaman produk memberi tahu harga flash
        $this->assertSame(100000, $this->variant->currentPrice());
        $this->get('/produk/mykonos-travel-size')->assertOk()
            ->assertSee('Rp100.000')
            ->assertSee('Flash Sale 12.12 (12 Desember, 00.00 WIB)');

        $sale->update(['show_teaser' => false]);
        $this->get('/')->assertDontSee('Flash Sale 12.12');

        $sale->update(['show_teaser' => true, 'is_active' => false]);
        $this->get('/')->assertDontSee('Flash Sale 12.12');
    }

    public function test_running_sale_price_applies_in_cart_and_order(): void
    {
        $this->sale('2026-11-16 00:00', '2026-11-16 23:59', 80000);

        $this->get('/')->assertOk()->assertSee('Berakhir dalam')->assertSee('Sedang berlangsung');
        $this->get('/produk/mykonos-travel-size')->assertSee('Rp80.000')->assertSee('Flash Sale 11.16');

        $user = User::factory()->create(['phone' => '081234567890']);
        $user->addresses()->create([
            'recipient_name' => $user->name, 'phone' => '081234567890', 'address_line' => 'Jl. Kaliurang 5',
            'district' => 'Depok', 'city' => 'Sleman', 'province' => 'DI Yogyakarta', 'postal_code' => '55281', 'is_primary' => true,
        ]);
        $this->actingAs($user)->post('/keranjang', ['variant_id' => $this->variant->id, 'quantity' => 2]);
        $this->get('/keranjang')->assertOk()->assertSee('Rp160.000')->assertSee('Flash Sale 11.16');

        Livewire::test(Checkout::class)->call('place')->assertHasNoErrors();
        $order = Order::latest('id')->first();
        $this->assertSame(160000, $order->subtotal);
        $this->assertSame(80000, $order->items->firstWhere('is_gift', false)->price);

        // Selewat jam sale harga kembali normal dan seksi hilang dari Beranda
        $this->travelTo(CarbonImmutable::parse('2026-11-17 00:00:01'));
        $this->assertSame(100000, $this->variant->fresh()->currentPrice());
        $this->get('/')->assertDontSee('Flash Sale 11.16');
    }

    public function test_admin_creates_flash_sale_from_twin_date(): void
    {
        $this->actingAs(User::factory()->create(['role' => Role::Admin]));
        Filament::setCurrentPanel('admin');
        $this->get('/admin/flash-sale')->assertOk();
        $undoRepeaterFake = Repeater::fake();

        Livewire::test(CreateFlashSale::class)
            ->fillForm(['tanggal_kembar' => '2026-12-12'])
            ->assertFormSet(['name' => 'Flash Sale 12.12', 'starts_at' => '2026-12-12 00:00', 'ends_at' => '2026-12-12 23:59'])
            ->fillForm(['items' => [['product_variant_id' => $this->variant->id, 'sale_price' => 100000]]])
            ->call('create')
            ->assertHasFormErrors(['items.0.sale_price'])
            ->fillForm(['items' => [['product_variant_id' => $this->variant->id, 'sale_price' => 75000]]])
            ->call('create')
            ->assertHasNoFormErrors();

        $undoRepeaterFake();

        $sale = FlashSale::with('items')->sole();
        $this->assertSame('Flash Sale 12.12', $sale->name);
        $this->assertSame(75000, $sale->items->sole()->sale_price);
        $this->assertSame(25, $sale->items->sole()->discountPercent());
    }

    public function test_example_command_creates_next_twin_date_sale_once(): void
    {
        $this->artisan('toko:contoh-flash-sale')->assertSuccessful();
        $this->artisan('toko:contoh-flash-sale')->expectsOutputToContain('Sudah ada flash sale')->assertSuccessful();

        $sale = FlashSale::with('items')->sole();
        $this->assertSame('Flash Sale 12.12', $sale->name);
        $this->assertSame(80000, $sale->items->sole()->sale_price);
    }

    private function sale(string $start, string $end, int $price): FlashSale
    {
        $starts = CarbonImmutable::parse($start);
        $sale = FlashSale::create([
            'name' => "Flash Sale {$starts->month}.{$starts->day}", 'starts_at' => $starts, 'ends_at' => CarbonImmutable::parse($end),
            'is_active' => true, 'show_teaser' => true,
        ]);
        $sale->items()->create(['product_variant_id' => $this->variant->id, 'sale_price' => $price]);

        return $sale;
    }
}
