<div class="flex flex-col gap-6">
    @if ($editing)
        <form wire:submit="save" class="flex flex-col gap-6 rounded-3xl bg-white p-6 sm:p-8">
            <h2 class="text-xl font-bold">{{ $form->id ? 'Ubah alamat' : 'Alamat baru' }}</h2>
            @include('livewire.partials.address-fields')
            <div class="flex flex-wrap gap-3">
                <button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="save">Simpan alamat</button>
                <button type="button" wire:click="cancel" class="btn-outline">Batal</button>
            </div>
        </form>
    @else
        <div class="flex flex-wrap items-center justify-between gap-3">
            <p class="text-[15px] text-ink-soft">Alamat utama dipilih otomatis saat checkout.</p>
            <button type="button" wire:click="create" class="btn-primary">Tambah alamat</button>
        </div>
    @endif

    @if ($this->addresses->isEmpty() && ! $editing)
        <div class="card-dashed flex flex-col items-start gap-3 p-6">
            <p class="text-[15px]">Belum ada alamat. Tambahkan alamat pengiriman supaya checkout lebih cepat.</p>
        </div>
    @endif

    <ul class="grid gap-4 md:grid-cols-2">
        @foreach ($this->addresses as $address)
            <li wire:key="alamat-{{ $address->id }}"
                @class(['flex flex-col gap-4 rounded-3xl bg-white p-6', 'ring-2 ring-ink' => $address->is_primary])>
                <div class="flex flex-wrap items-center gap-2">
                    <span class="font-bold">{{ $address->recipient_name }}</span>
                    @if ($address->label)
                        <span class="rounded-full bg-cream px-3 py-0.5 text-sm">{{ $address->label }}</span>
                    @endif
                    @if ($address->is_primary)
                        <span class="rounded-full bg-ink px-3 py-0.5 text-sm font-semibold text-white">Utama</span>
                    @endif
                </div>
                <div class="flex flex-col gap-1 text-[15px]">
                    <p>{{ $address->phone }}</p>
                    <p class="text-ink-soft">{{ $address->oneLine() }}</p>
                </div>
                <div class="mt-auto flex flex-wrap gap-x-5 gap-y-1 text-[15px] font-semibold">
                    <button type="button" wire:click="edit({{ $address->id }})" class="min-h-[44px] underline underline-offset-4">Ubah</button>
                    @unless ($address->is_primary)
                        <button type="button" wire:click="makePrimary({{ $address->id }})" class="min-h-[44px] underline underline-offset-4">Jadikan utama</button>
                    @endunless
                    <button type="button" wire:click="delete({{ $address->id }})" wire:confirm="Hapus alamat ini?"
                            class="min-h-[44px] text-red-700 underline underline-offset-4">Hapus</button>
                </div>
            </li>
        @endforeach
    </ul>
</div>
