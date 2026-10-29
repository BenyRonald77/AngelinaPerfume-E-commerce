<?php

namespace App\Livewire;

use App\Livewire\Forms\AddressForm;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

/** Buku alamat di Akun saya */
class AddressBook extends Component
{
    public AddressForm $form;

    public bool $editing = false;

    #[Computed]
    public function addresses(): Collection
    {
        return auth()->user()->addresses()->get();
    }

    public function create(): void
    {
        $this->form->startNew(auth()->user());
        $this->editing = true;
    }

    public function edit(int $id): void
    {
        $this->form->resetValidation();
        $this->form->fillFrom(auth()->user()->addresses()->findOrFail($id));
        $this->editing = true;
    }

    public function cancel(): void
    {
        $this->form->reset();
        $this->form->resetValidation();
        $this->editing = false;
    }

    public function save(): void
    {
        $this->form->save(auth()->user());
        $this->editing = false;
        unset($this->addresses);
        $this->dispatch('toko-pesan', pesan: 'Alamat disimpan.');
    }

    public function makePrimary(int $id): void
    {
        $user = auth()->user();
        $address = $user->addresses()->findOrFail($id);
        $user->addresses()->update(['is_primary' => false]);
        $address->update(['is_primary' => true]);
        unset($this->addresses);
    }

    public function delete(int $id): void
    {
        $user = auth()->user();
        $address = $user->addresses()->findOrFail($id);
        $address->delete();

        // Alamat utama dihapus: alamat tertua berikutnya menggantikan
        if ($address->is_primary && ($next = $user->addresses()->reorder()->orderBy('id')->first())) {
            $next->update(['is_primary' => true]);
        }

        unset($this->addresses);
        $this->dispatch('toko-pesan', pesan: 'Alamat dihapus.');
    }

    public function render()
    {
        return view('livewire.address-book');
    }
}
