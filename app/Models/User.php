<?php

namespace App\Models;

use App\Enums\Role;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser, MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $attributes = [
        'role' => 'pembeli',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'google_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => Role::class,
        ];
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class)->orderByDesc('is_primary')->orderBy('id');
    }

    public function primaryAddress(): HasOne
    {
        return $this->hasOne(Address::class)->ofMany(['is_primary' => 'max', 'id' => 'min']);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class)->latest('id');
    }

    public function cart(): HasOne
    {
        return $this->hasOne(Cart::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === Role::Admin;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        // Staf gudang nanti mendapat akses terbatas; untuk sekarang hanya admin
        return $this->isAdmin();
    }
}
