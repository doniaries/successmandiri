<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Filament\Panel;
use App\Models\Team;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Filament\Models\Contracts\HasTenants;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
// use Filament\Models\Contracts\FilamentUser;

class User extends Authenticatable implements HasTenants
// implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;



    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_active',


    ];


    // public function canAccessPanel(Panel $panel): bool
    // {
    //     return str_ends_with($this->email, 'doniaries@gmail.com') && $this->hasVerifiedEmail();
    // }

    protected $hidden = [
        'password',
        'remember_token',
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

        ];
    }

    //multi tenancy
    // Relasi many-to-many dengan Team melalui team_user
    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'team_user')
            ->withTimestamps();
    }

    public function getTenants(Panel $panel): Collection
    {
        // Superadmin bisa lihat semua team
        if ($this->email === 'superadmin@gmail.com') {
            return Team::all();
        }

        // User lain hanya lihat team mereka
        return $this->teams;
    }

    public function canAccessTenant(Model $tenant): bool
    {
        // Superadmin bisa akses semua team
        if ($this->email === 'superadmin@gmail.com') {
            return true;
        }

        // User lain hanya bisa akses team mereka
        return $this->teams->contains($tenant->id);
    }
}
