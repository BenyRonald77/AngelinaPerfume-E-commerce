<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    /**
     * Update the user's password.
     */
    public function update(Request $request): RedirectResponse
    {
        // Akun dari Google belum punya password: boleh langsung mengatur tanpa password lama
        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => $request->user()->password ? ['required', 'current_password'] : ['nullable'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('flash', 'Password disimpan.');
    }
}
