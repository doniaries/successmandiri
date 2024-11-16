<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Team extends Model
{
    protected $table = 'teams';

    protected $fillable = [
        'name',
        'slug',
        'saldo',
        'alamat',
        'telepon',
        'email',
        'pimpinan',
        'npwp',
        'is_active',
        'keterangan',

    ];


    // Scope untuk team yang aktif
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Relasi many-to-many dengan User melalui team_user
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'team_user')
            ->withTimestamps();
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'team_user');
    }

    public function canAccessTenant(Model $user): bool
    {
        // Superadmin bisa akses semua
        if ($user->email === 'superadmin@gmail.com') {
            return true;
        }

        // User lain hanya bisa akses team mereka
        return $user->teams->contains($this->id);
    }

    public static function getTenantLabel(): string
    {
        return 'Team';
    }

    public function getFilamentName(): string
    {
        return $this->name;
    }
}
