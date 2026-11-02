<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Filament\Tinjau\Resources\Stock\Pages\ListStock;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductAroma;
use App\Models\ProductVariant;
use App\Models\Size;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class ReviewerPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_reviewer_sees_stock_but_cannot_open_admin(): void
    {
        Category::ensureDefaults();
        $product = Product::create([
            'number' => 12, 'name' => 'Zataru Signature', 'slug' => 'zataru-signature',
            'category_id' => Category::where('slug', Category::DECANT)->value('id'),
        ]);
        $aroma = ProductAroma::create(['product_id' => $product->id, 'number' => 3, 'name' => 'Topaz']);
        ProductVariant::create([
            'product_id' => $product->id, 'product_aroma_id' => $aroma->id,
            'size_id' => Size::where('ml', 2)->value('id'), 'sku' => 'AP-0012-03-2ML',
            'shopee_variation_id' => '257302961070', 'price' => 20000, 'weight_grams' => 60, 'stock' => 4,
        ]);

        $this->actingAs(User::factory()->create(['role' => Role::Peninjau]));

        $this->get('/tinjau/stok')->assertOk()->assertSee('AP-0012-03-2ML')->assertSee('257302961070');
        $this->get('/admin')->assertForbidden();
        $this->get('/admin/pesanan')->assertForbidden();
        $this->get('/admin/pembeli')->assertForbidden();
        $this->get('/admin/stok')->assertForbidden();

        Filament::setCurrentPanel('tinjau');
        Livewire::test(ListStock::class)
            ->assertSee('Sinkron stok dengan Shopee sedang dikembangkan')
            ->assertDontSee('Ubah stok');
    }

    public function test_buyer_cannot_open_reviewer_panel(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/tinjau/stok')->assertForbidden();
    }

    public function test_command_creates_account_and_rotates_password(): void
    {
        $this->assertSame(0, Artisan::call('toko:akun-peninjau'));
        $user = User::where('email', 'peninjau@angelia.test')->sole();
        $this->assertSame(Role::Peninjau, $user->role);
        $first = $user->password;
        preg_match('/Password\s+: (\S+)/', Artisan::output(), $match);
        $this->assertTrue(Hash::check($match[1], $first));

        Artisan::call('toko:akun-peninjau');
        $this->assertNotSame($first, $user->fresh()->password);
        $this->assertSame(1, User::where('email', 'peninjau@angelia.test')->count());
    }
}
