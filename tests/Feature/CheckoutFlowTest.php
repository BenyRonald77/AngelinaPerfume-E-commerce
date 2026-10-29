<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\Role;
use App\Filament\Resources\Orders\Pages\ViewOrder;
use App\Livewire\AddressBook;
use App\Livewire\CartPage;
use App\Livewire\Checkout;
use App\Models\Address;
use App\Models\Category;
use App\Models\GiftRule;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductAroma;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Models\ShippingRate;
use App\Models\Size;
use App\Models\User;
use App\Notifications\OrderStatusChanged;
use App\Services\OrderWorkflow;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Livewire\Livewire;
use Tests\TestCase;

class CheckoutFlowTest extends TestCase
{
    use RefreshDatabase;

    private ProductVariant $decant;

    private ProductVariant $fullSize;

    private ProductVariant $gift;

    protected function setUp(): void
    {
        parent::setUp();

        Category::ensureDefaults();
        Notification::fake();
        // Email pesanan memakai defer(); di tes dijalankan langsung
        $this->withoutDefer();

        $this->decant = $this->variant('Zataru Signature', Category::DECANT, 'Topaz', 2, 20000, 10);
        $this->fullSize = $this->variant('MYKONOS Penthouse', Category::FULL_SIZE, 'Penthouse', 100, 450000, 5, maxPerOrder: 1);
        $this->gift = $this->variant('Free decant', Category::HADIAH, 'Acak', 2, 0, 3);

        ShippingRate::create(['province' => 'DI Yogyakarta', 'courier' => 'J&T EZ', 'first_kg_price' => 10000, 'next_kg_price' => 8000, 'estimate' => '1-2 hari']);
    }

    public function test_guest_cart_is_kept_after_login(): void
    {
        $this->post('/keranjang', ['variant_id' => $this->decant->id, 'quantity' => 2])->assertSessionHas('flash');
        $this->get('/keranjang')->assertOk()->assertSee('Zataru Signature')->assertSee('Rp40.000');

        $user = User::factory()->create();
        $this->post('/masuk', ['email' => $user->email, 'password' => 'password'])->assertRedirect();

        $this->assertSame(2, $user->cart->items()->sum('quantity'));
    }

    public function test_quantity_is_capped_by_purchase_limit_and_stock(): void
    {
        $this->post('/keranjang', ['variant_id' => $this->fullSize->id, 'quantity' => 3])
            ->assertSessionHas('flash', 'Jumlah disesuaikan menjadi 1: maksimal 1 per pesanan.');

        $this->post('/keranjang', ['variant_id' => $this->gift->id, 'quantity' => 1])
            ->assertSessionHas('flash_error', 'Produk ini tidak dijual.');
    }

    public function test_buy_now_sends_guest_to_login_then_checkout(): void
    {
        $this->post('/keranjang', ['variant_id' => $this->decant->id, 'quantity' => 1, 'buy_now' => 1])->assertRedirect('/checkout');
        $this->get('/checkout')->assertRedirect('/masuk');
    }

    public function test_checkout_places_order_locks_stock_and_adds_gift(): void
    {
        GiftRule::create(['name' => 'Free decant', 'gift_variant_id' => $this->gift->id, 'min_subtotal' => 100000, 'is_active' => true]);
        [$user, $address] = $this->buyerWithAddress();
        $this->actingAs($user)->post('/keranjang', ['variant_id' => $this->decant->id, 'quantity' => 6]);

        // 6 × 60 gram + extra dus 20 gram = 380 gram: dibulatkan 1 kg
        Livewire::test(Checkout::class)
            ->assertSet('addressId', $address->id)
            ->assertSet('shippingKey', 'manual-'.ShippingRate::value('id'))
            ->set('packaging', true)
            ->assertSee('Rp10.000')
            ->assertSee('Rp131.700')
            ->call('place')
            ->assertHasNoErrors()
            ->assertRedirect();

        $order = Order::with('items', 'shipment', 'payment')->sole();
        $this->assertSame(OrderStatus::MenungguPembayaran, $order->status);
        $this->assertSame(120000, $order->subtotal);
        $this->assertSame(1700, $order->packaging_fee);
        $this->assertSame(10000, $order->shipping_cost);
        $this->assertSame(131700, $order->total);
        $this->assertSame('Jl. Kaliurang 5', $order->address_line);
        $this->assertSame('J&T EZ', $order->shipment->courier);
        $this->assertTrue($order->payment_due_at->between(now()->addHours(23), now()->addHours(25)));

        $this->assertSame(4, $this->decant->fresh()->stock);
        $this->assertSame(2, $this->gift->fresh()->stock);
        $giftItem = $order->items->firstWhere('is_gift', true);
        $this->assertSame(0, $giftItem->price);

        $this->assertSame(0, $user->cart->items()->count());
        Notification::assertSentTo($user, OrderStatusChanged::class);

        // Halaman bayar tanpa kunci Xendit: arahkan ke admin via WhatsApp
        $this->get("/pesanan/{$order->code}/bayar")->assertOk()->assertSee('Pembayaran online sedang disiapkan');
    }

    public function test_province_without_rate_cannot_checkout(): void
    {
        [$user] = $this->buyerWithAddress('Papua');
        $this->actingAs($user)->post('/keranjang', ['variant_id' => $this->decant->id, 'quantity' => 1]);

        Livewire::test(Checkout::class)
            ->assertSet('shippingKey', null)
            ->assertSee('Ongkir ke Papua belum tersedia')
            ->call('place')
            ->assertHasErrors('checkout');

        $this->assertSame(0, Order::count());
        $this->assertSame(10, $this->decant->fresh()->stock);
    }

    public function test_last_stock_cannot_be_sold_twice(): void
    {
        [$user] = $this->buyerWithAddress();
        $this->actingAs($user)->post('/keranjang', ['variant_id' => $this->decant->id, 'quantity' => 3]);
        $this->decant->update(['stock' => 2]);

        Livewire::test(Checkout::class)->call('place')->assertHasErrors('checkout');

        $this->assertSame(0, Order::count());
        $this->assertSame(2, $this->decant->fresh()->stock);
    }

    public function test_cart_page_updates_quantity(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post('/keranjang', ['variant_id' => $this->decant->id, 'quantity' => 1]);
        $item = $user->cart->items()->first();

        Livewire::test(CartPage::class)
            ->call('increment', $item->id)
            ->assertDispatched('cart-count', count: 2)
            ->assertSee('Rp40.000')
            ->call('remove', $item->id)
            ->assertSee('Keranjang masih kosong');
    }

    public function test_unpaid_orders_expire_and_return_stock(): void
    {
        $order = $this->placeOrder();
        $this->travel(25)->hours();

        $this->artisan('toko:batalkan-kedaluwarsa')->assertSuccessful();

        $this->assertSame(OrderStatus::Kedaluwarsa, $order->fresh()->status);
        $this->assertSame(10, $this->decant->fresh()->stock);
    }

    public function test_xendit_webhook_marks_order_paid(): void
    {
        config(['services.xendit.callback_token' => 'rahasia-uji']);
        $order = $this->placeOrder();

        $this->postJson('/webhooks/xendit', ['external_id' => $order->code, 'status' => 'PAID', 'paid_amount' => $order->total])
            ->assertUnauthorized();

        $this->withHeader('x-callback-token', 'rahasia-uji')
            ->postJson('/webhooks/xendit', ['external_id' => $order->code, 'status' => 'PAID', 'paid_amount' => $order->total - 1])
            ->assertStatus(422);

        $this->withHeader('x-callback-token', 'rahasia-uji')
            ->postJson('/webhooks/xendit', ['id' => 'inv_123', 'external_id' => $order->code, 'status' => 'PAID', 'paid_amount' => $order->total, 'payment_method' => 'QR_CODE', 'payment_channel' => 'QRIS'])
            ->assertOk();

        $order->refresh();
        $this->assertSame(OrderStatus::Lunas, $order->status);
        $this->assertSame('paid', $order->payment->status);
        $this->assertSame('QR_CODE QRIS', $order->payment->method);

        // Notifikasi berulang tidak mengubah apa pun
        $this->withHeader('x-callback-token', 'rahasia-uji')
            ->postJson('/webhooks/xendit', ['external_id' => $order->code, 'status' => 'PAID', 'paid_amount' => $order->total])
            ->assertOk();
        $this->assertSame(OrderStatus::Lunas, $order->fresh()->status);
    }

    public function test_pay_button_redirects_to_xendit_invoice(): void
    {
        config(['services.xendit.secret_key' => 'xnd_development_uji']);
        Http::fake(['api.xendit.co/v2/invoices' => Http::response(['id' => 'inv_9', 'invoice_url' => 'https://checkout-staging.xendit.co/web/inv_9'])]);

        $order = $this->placeOrder();

        $this->assertSame('https://checkout-staging.xendit.co/web/inv_9', $order->payment()->value('invoice_url'));
        $this->post("/pesanan/{$order->code}/bayar")->assertRedirect('https://checkout-staging.xendit.co/web/inv_9');
        Http::assertSent(fn ($request) => $request['external_id'] === $order->code && $request['amount'] === $order->total);
    }

    public function test_full_status_flow_and_buyer_confirmation(): void
    {
        $order = $this->placeOrder();
        $workflow = app(OrderWorkflow::class);

        $workflow->transition($order, OrderStatus::Lunas, data: ['method' => 'Transfer bank']);
        $workflow->transition($order, OrderStatus::Diproses);

        try {
            $workflow->transition($order, OrderStatus::Dikirim, data: ['courier' => 'J&T EZ']);
            $this->fail('Resi wajib diisi');
        } catch (InvalidArgumentException) {
        }

        $workflow->transition($order, OrderStatus::Dikirim, data: ['courier' => 'J&T EZ', 'tracking_number' => 'JX123456']);
        $this->get("/akun/pesanan/{$order->code}")->assertOk()->assertSee('JX123456')->assertSee('Lacak paket');

        $this->post("/akun/pesanan/{$order->code}/diterima")->assertRedirect();
        $this->assertSame(OrderStatus::Selesai, $order->fresh()->status);

        $this->expectException(InvalidArgumentException::class);
        $workflow->transition($order->fresh(), OrderStatus::Dibatalkan);
    }

    public function test_cancel_returns_stock_and_orders_are_private(): void
    {
        $order = $this->placeOrder();
        app(OrderWorkflow::class)->transition($order, OrderStatus::Dibatalkan, data: ['reason' => 'Stok aroma habis']);

        $this->assertSame(10, $this->decant->fresh()->stock);
        $this->get("/akun/pesanan/{$order->code}")->assertSee('Stok aroma habis');

        $this->actingAs(User::factory()->create())->get("/akun/pesanan/{$order->code}")->assertNotFound();
    }

    public function test_address_book_keeps_one_primary_address(): void
    {
        [$user, $first] = $this->buyerWithAddress();
        $this->actingAs($user);

        Livewire::test(AddressBook::class)
            ->call('create')
            ->set('form.recipient_name', 'Rina')
            ->set('form.phone', '081298765432')
            ->set('form.address_line', 'Jl. Malioboro 10')
            ->set('form.district', 'Gondomanan')
            ->set('form.city', 'Yogyakarta')
            ->set('form.province', 'DI Yogyakarta')
            ->set('form.postal_code', '55122')
            ->set('form.is_primary', true)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertFalse($first->fresh()->is_primary);
        $this->assertSame(1, $user->addresses()->where('is_primary', true)->count());

        Livewire::test(AddressBook::class)
            ->call('create')
            ->set('form.province', 'Atlantis')
            ->call('save')
            ->assertHasErrors(['form.province', 'form.address_line']);
    }

    public function test_admin_order_pages_open(): void
    {
        $order = $this->placeOrder();
        $admin = User::factory()->create(['role' => Role::Admin]);
        $this->actingAs($admin);

        foreach (['/admin', '/admin/pesanan', "/admin/pesanan/{$order->code}", '/admin/ongkir', '/admin/pembeli', '/admin/pengaturan', "/admin/cetak/pesanan/{$order->code}"] as $url) {
            $this->get($url)->assertOk();
        }

        $this->actingAs($order->user)->get("/admin/cetak/pesanan/{$order->code}")->assertForbidden();
    }

    public function test_admin_actions_move_order_through_workflow(): void
    {
        $order = $this->placeOrder();
        $this->actingAs(User::factory()->create(['role' => Role::Admin]));
        Filament::setCurrentPanel('admin');

        Livewire::test(ViewOrder::class, ['record' => $order->code])
            ->assertActionVisible('markPaid')
            ->assertActionHidden('ship')
            ->callAction('markPaid', data: ['method' => 'Transfer bank', 'note' => 'BCA 14.10'])
            ->assertHasNoFormErrors()
            ->callAction('process')
            ->callAction('ship', data: ['courier' => 'J&T EZ', 'tracking_number' => ''])
            ->assertHasFormErrors(['tracking_number' => 'required']);

        $this->assertSame(OrderStatus::Diproses, $order->fresh()->status);

        Livewire::test(ViewOrder::class, ['record' => $order->code])
            ->callAction('ship', data: ['courier' => 'J&T EZ', 'tracking_number' => 'JX999'])
            ->assertHasNoFormErrors();

        $order->refresh();
        $this->assertSame(OrderStatus::Dikirim, $order->status);
        $this->assertSame('Transfer bank', $order->payment->method);
        $this->assertSame('JX999', $order->shipment->tracking_number);
    }

    public function test_settings_change_checkout_values(): void
    {
        Setting::put('packaging_fee', '2500');
        [$user] = $this->buyerWithAddress();
        $this->actingAs($user)->post('/keranjang', ['variant_id' => $this->decant->id, 'quantity' => 1]);

        Livewire::test(Checkout::class)->set('packaging', true)->call('place');

        $this->assertSame(2500, Order::sole()->packaging_fee);
    }

    /** Pesanan 1 × Zataru oleh pembeli di DI Yogyakarta; pembeli tetap login */
    private function placeOrder(): Order
    {
        [$user] = $this->buyerWithAddress();
        $this->actingAs($user)->post('/keranjang', ['variant_id' => $this->decant->id, 'quantity' => 1]);
        Livewire::test(Checkout::class)->call('place')->assertHasNoErrors();

        $order = Order::latest('id')->first();
        $this->assertSame(9, $this->decant->fresh()->stock);

        return $order;
    }

    /** @return array{User, Address} */
    private function buyerWithAddress(string $province = 'DI Yogyakarta'): array
    {
        $user = User::factory()->create(['phone' => '081234567890']);
        $address = $user->addresses()->create([
            'recipient_name' => $user->name, 'phone' => '081234567890', 'address_line' => 'Jl. Kaliurang 5',
            'district' => 'Depok', 'city' => 'Sleman', 'province' => $province, 'postal_code' => '55281', 'is_primary' => true,
        ]);

        return [$user, $address];
    }

    private function variant(string $name, string $category, string $aromaName, float $ml, int $price, int $stock, ?int $maxPerOrder = null): ProductVariant
    {
        $product = Product::create([
            'number' => (int) Product::max('number') + 1,
            'name' => $name,
            'slug' => Str::slug($name),
            'category_id' => Category::where('slug', $category)->value('id'),
        ]);
        $aroma = ProductAroma::create(['product_id' => $product->id, 'number' => 1, 'name' => $aromaName]);
        $size = Size::where('ml', $ml)->first();

        return ProductVariant::create([
            'product_id' => $product->id, 'product_aroma_id' => $aroma->id, 'size_id' => $size?->id,
            'sku' => ProductVariant::generateSku($product, $aroma, $size),
            'price' => $price, 'weight_grams' => 60, 'stock' => $stock, 'max_per_order' => $maxPerOrder,
        ]);
    }
}
