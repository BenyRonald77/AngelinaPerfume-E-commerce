<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

/**
 * Akun saya: profil dan ganti password. Hapus akun sengaja tidak disediakan:
 * riwayat pesanan pembeli harus tetap tersimpan.
 */
class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('akun.profil', [
            'user' => $request->user(),
            'activeOrders' => $request->user()->orders()->whereIn('status', ['menunggu_pembayaran', 'lunas', 'diproses', 'dikirim'])->count(),
        ]);
    }

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('akun')->with('flash', 'Profil disimpan.');
    }
}
