<?php

namespace App\Livewire;

use App\Livewire\Forms\AddressForm;
use App\Models\Address;
use App\Services\CartException;
use App\Services\CartService;
use App\Services\OrderService;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

/** Checkout (F4): alamat, pengiriman, extra dus, catatan, lalu pesanan dibuat dan stok dikunci */
class Checkout extends Component
{
    public AddressForm $form;

    public ?int $addressId = null;

    public ?string $shippingKey = null;

    public bool $packaging = false;

    public string $note = '';

    public bool $addingAddress = false;

    public function mount()
    {
        if (app(CartService::class)->lines()->isEmpty()) {
            return $this->redirectRoute('keranjang');
        }

        $this->addressId = auth()->user()->addresses()->value('id');
        if (! $this->addressId) {
            $this->addAddress();
        }
        $this->pickFirstOption();
    }

    #[Computed]
    public function addresses(): Collection
    {
        return auth()->user()->addresses()->get();
    }

    #[Computed]
    public function address(): ?Address
    {
        return $this->addresses->firstWhere('id', $this->addressId);
    }

    #[Computed]
    public function quote(): array
    {
        return app(OrderService::class)->quote($this->address, $this->packaging);
    }

    #[Computed]
    public function shipping()
    {
        return collect($this->quote['options'])->firstWhere('key', $this->shippingKey);
    }

    public function updatedAddressId(): void
    {
        $this->pickFirstOption();
    }

    /** Berat dus mengubah ongkir; pilihan kurir tetap bila masih tersedia */
    public function updatedPackaging(): void
    {
        unset($this->quote, $this->shipping);
        if (! $this->shipping) {
            $this->pickFirstOption();
        }
    }

    public function addAddress(): void
    {
        $this->form->startNew(auth()->user());
        $this->addingAddress = true;
    }

    public function cancelAddress(): void
    {
        $this->form->reset();
        $this->form->resetValidation();
        $this->addingAddress = false;
    }

    public function saveAddress(): void
    {
        $address = $this->form->save(auth()->user());
        unset($this->addresses);
        $this->addressId = $address->id;
        $this->addingAddress = false;
        $this->pickFirstOption();
    }

    public function place(OrderService $orders)
    {
        if (! $this->address) {
            $this->addError('checkout', 'Pilih atau tambahkan alamat pengiriman dulu.');

            return null;
        }
        if (! $this->shippingKey) {
            $this->addError('checkout', 'Pilih pengiriman dulu.');

            return null;
        }

        try {
            $order = $orders->place(auth()->user(), $this->address, $this->shippingKey, $this->packaging, $this->note);
        } catch (CartException $e) {
            unset($this->quote, $this->shipping);
            $this->addError('checkout', $e->getMessage());

            return null;
        }

        return $this->redirectRoute('pesanan.bayar', $order);
    }

    public function render()
    {
        return view('livewire.checkout');
    }

    private function pickFirstOption(): void
    {
        unset($this->address, $this->quote, $this->shipping);
        $this->shippingKey = $this->quote['options'][0]->key ?? null;
    }
}
