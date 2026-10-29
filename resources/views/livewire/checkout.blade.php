@php
    use App\Models\Setting;
    use App\Services\Xendit;
    use App\Support\Rupiah;
    use App\Support\WhatsApp;

    $quote = $this->quote;
    $shipping = $this->shipping;
    $problems = $quote['lines']->filter(fn ($line) => $line['problem']);
    $total = $quote['subtotal'] + $quote['packaging_fee'] + ($shipping?->cost ?? 0);
    $radioCard = 'flex min-h-[56px] cursor-pointer gap-3 rounded-2xl border-2 border-dusty-pink/60 bg-white p-4 transition-colors hover:border-ink has-[:checked]:border-ink has-[:checked]:bg-blush/40';
@endphp

<div class="mx-auto flex max-w-6xl flex-col gap-8 px-4 pb-20 pt-8 sm:px-6 lg:px-8">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <h1 class="font-display text-[34px] leading-tight sm:text-[40px]">Checkout</h1>
        <a href="{{ route('keranjang') }}" class="inline-flex min-h-[44px] items-center text-[15px] font-semibold underline underline-offset-4">Ubah keranjang</a>
    </div>

    <div class="grid gap-8 lg:grid-cols-[1fr_380px] lg:items-start">
        <div class="flex flex-col gap-6">
            {{-- 1. Alamat --}}
            <section aria-labelledby="judul-alamat" class="flex flex-col gap-5 rounded-3xl bg-white p-6 sm:p-8">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h2 id="judul-alamat" class="text-xl font-bold">Alamat pengiriman</h2>
                    @if (! $addingAddress && $this->addresses->isNotEmpty())
                        <button type="button" wire:click="addAddress" class="min-h-[44px] text-[15px] font-semibold underline underline-offset-4">Tambah alamat</button>
                    @endif
                </div>

                @if ($addingAddress)
                    <form wire:submit="saveAddress" class="flex flex-col gap-6">
                        @include('livewire.partials.address-fields', ['prefix' => 'checkout'])
                        <div class="flex flex-wrap gap-3">
                            <button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="saveAddress">Simpan dan pakai alamat ini</button>
                            @if ($this->addresses->isNotEmpty())
                                <button type="button" wire:click="cancelAddress" class="btn-outline">Batal</button>
                            @endif
                        </div>
                    </form>
                @else
                    <fieldset class="flex flex-col gap-3">
                        <legend class="sr-only">Pilih alamat</legend>
                        @foreach ($this->addresses as $address)
                            <label wire:key="pilih-alamat-{{ $address->id }}" class="{{ $radioCard }}">
                                <input type="radio" wire:model.live="addressId" value="{{ $address->id }}" class="mt-1 h-5 w-5 shrink-0 border-ink-soft text-ink focus:ring-ink">
                                <span class="flex flex-col gap-0.5 text-[15px]">
                                    <span class="font-semibold">{{ $address->recipient_name }} · {{ $address->phone }}@if ($address->label) <span class="font-normal text-ink-soft">({{ $address->label }})</span>@endif</span>
                                    <span class="text-ink-soft">{{ $address->oneLine() }}</span>
                                </span>
                            </label>
                        @endforeach
                    </fieldset>
                @endif
            </section>

            {{-- 2. Pengiriman --}}
            <section aria-labelledby="judul-kirim" class="flex flex-col gap-5 rounded-3xl bg-white p-6 sm:p-8">
                <div class="flex flex-col gap-1">
                    <h2 id="judul-kirim" class="text-xl font-bold">Pengiriman</h2>
                    <p class="text-[15px] text-ink-soft">Berat paket {{ number_format($quote['weight'] / 1000, 2, ',', '.') }} kg, dibulatkan ke atas per kg.</p>
                </div>

                @if (! $this->address)
                    <p class="text-[15px] text-ink-soft">Pilih alamat dulu untuk melihat ongkir.</p>
                @elseif (empty($quote['options']))
                    <div class="card-dashed flex flex-col items-start gap-3 p-5">
                        <p class="text-[15px]">Ongkir ke {{ $this->address->province }} belum tersedia, hubungi kami via WhatsApp.</p>
                        @if ($wa = WhatsApp::link("Halo Angelia Perfume, saya mau pesan dengan pengiriman ke {$this->address->city}, {$this->address->province}. Berapa ongkirnya?"))
                            <a href="{{ $wa }}" target="_blank" rel="noopener" class="btn-outline">Tanya via WhatsApp</a>
                        @else
                            <p class="text-[15px] font-semibold">WhatsApp {{ WhatsApp::display() }}</p>
                        @endif
                    </div>
                @else
                    <fieldset class="flex flex-col gap-3">
                        <legend class="sr-only">Pilih kurir</legend>
                        @foreach ($quote['options'] as $option)
                            <label wire:key="kurir-{{ $option->key }}" class="{{ $radioCard }} items-center">
                                <input type="radio" wire:model.live="shippingKey" value="{{ $option->key }}" class="h-5 w-5 shrink-0 border-ink-soft text-ink focus:ring-ink">
                                <span class="flex flex-1 flex-wrap items-center justify-between gap-x-4 gap-y-0.5 text-[15px]">
                                    <span class="flex flex-col">
                                        <span class="font-semibold">{{ $option->label() }}</span>
                                        @if ($option->estimate)
                                            <span class="text-ink-soft">Estimasi {{ $option->estimate }}</span>
                                        @endif
                                    </span>
                                    <span class="font-semibold">{{ Rupiah::format($option->cost) }}</span>
                                </span>
                            </label>
                        @endforeach
                    </fieldset>
                @endif
            </section>

            {{-- 3. Tambahan --}}
            <section aria-labelledby="judul-tambahan" class="flex flex-col gap-5 rounded-3xl bg-white p-6 sm:p-8">
                <h2 id="judul-tambahan" class="text-xl font-bold">Tambahan</h2>
                <label class="{{ $radioCard }} items-center">
                    <input type="checkbox" wire:model.live="packaging" class="h-5 w-5 shrink-0 rounded border-ink-soft text-ink focus:ring-ink">
                    <span class="flex flex-1 flex-wrap justify-between gap-x-4 text-[15px]">
                        <span><span class="font-semibold">Extra dus dan bubble wrap</span> <span class="text-ink-soft">untuk perlindungan ekstra</span></span>
                        <span class="font-semibold">{{ Rupiah::format(Setting::int('packaging_fee')) }}</span>
                    </span>
                </label>
                <div class="flex flex-col gap-1.5">
                    <x-input-label for="catatan" value="Catatan untuk penjual (opsional)" />
                    <textarea id="catatan" wire:model="note" rows="2" maxlength="500" placeholder="Contoh: tolong dicek kebocoran sebelum dikirim"
                              class="w-full rounded-2xl border-dusty-pink bg-white px-4 py-3 text-base focus:border-ink focus:ring-0"></textarea>
                </div>
            </section>
        </div>

        {{-- Ringkasan dan tombol buat pesanan --}}
        <aside aria-labelledby="judul-pesanan" class="flex flex-col gap-5 rounded-3xl bg-white p-6 lg:sticky lg:top-6">
            <h2 id="judul-pesanan" class="text-xl font-bold">Pesananmu</h2>

            <ul class="flex flex-col gap-3 text-[15px]">
                @foreach ($quote['lines'] as ['item' => $item, 'problem' => $problem])
                    <li wire:key="ringkas-{{ $item->id }}" class="flex justify-between gap-4">
                        <span class="min-w-0">
                            <span class="block font-semibold">{{ $item->variant->product->name }}</span>
                            <span class="block text-ink-soft">{{ $item->variant->aroma->name }}@if ($item->variant->size) · {{ $item->variant->size->label }}@endif · {{ $item->quantity }}×</span>
                            @if ($problem)
                                <span class="block text-sm font-medium text-red-700">{{ $problem }}</span>
                            @endif
                        </span>
                        <span class="shrink-0 font-semibold">{{ Rupiah::format($item->lineTotal()) }}</span>
                    </li>
                @endforeach
                @foreach ($quote['gifts'] as $rule)
                    <li class="flex justify-between gap-4">
                        <span class="min-w-0">
                            <span class="block font-semibold">{{ $rule->name }}</span>
                            <span class="block text-ink-soft">Hadiah otomatis</span>
                        </span>
                        <span class="shrink-0 font-semibold">Rp0</span>
                    </li>
                @endforeach
            </ul>

            <dl class="flex flex-col gap-2.5 border-t border-dusty-pink/40 pt-4 text-[15px]">
                <div class="flex justify-between gap-4"><dt>Subtotal</dt><dd>{{ Rupiah::format($quote['subtotal']) }}</dd></div>
                @if ($quote['packaging_fee'])
                    <div class="flex justify-between gap-4"><dt>Extra dus</dt><dd>{{ Rupiah::format($quote['packaging_fee']) }}</dd></div>
                @endif
                <div class="flex justify-between gap-4">
                    <dt>Ongkir</dt>
                    <dd>{{ $shipping ? Rupiah::format($shipping->cost) : '–' }}</dd>
                </div>
                <div class="flex justify-between gap-4 pt-2 text-xl font-bold">
                    <dt>Total</dt>
                    <dd>{{ Rupiah::format($total) }}</dd>
                </div>
            </dl>

            @error('checkout')
                <p class="text-sm font-medium text-red-700" role="alert">{{ $message }}</p>
            @enderror

            @if ($problems->isNotEmpty())
                <a href="{{ route('keranjang') }}" class="btn-outline h-14 w-full">Perbaiki keranjang</a>
            @else
                <button type="button" wire:click="place" wire:loading.attr="disabled" wire:target="place"
                        @disabled(! $shipping || $addingAddress) class="btn-primary h-14 w-full">
                    <span wire:loading.remove wire:target="place">Buat pesanan</span>
                    <span wire:loading wire:target="place">Membuat pesanan…</span>
                </button>
            @endif

            <p class="text-sm text-ink-soft">
                Stok dikunci untukmu begitu pesanan dibuat. Bayar dalam {{ Setting::int('payment_window_hours') }} jam; lewat dari itu pesanan batal otomatis.
                @if (Xendit::enabled())
                    Pembayaran lewat virtual account, e-wallet, atau QRIS.
                @endif
            </p>
        </aside>
    </div>
</div>
