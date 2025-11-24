<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
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

    // Role helper methods
    public function isSuperAdmin()
    {
        return $this->role === 'super_admin';
    }

    public function isAdmin()
    {
        return in_array($this->role, ['super_admin', 'admin']);
    }

    public function isTechnician()
    {
        return $this->role === 'technician';
    }

    public function hasRole(...$roles)
    {
        return in_array($this->role, $roles);
    }

    public function getRoleLabelAttribute()
    {
        $labels = [
            'super_admin' => 'Super Admin',
            'admin' => 'Admin',
            'technician' => 'Technician',
            'viewer' => 'Viewer',
        ];
        
        return $labels[$this->role] ?? 'Unknown';
    }

    public function getRoleBadgeColorAttribute()
    {
        $colors = [
            'super_admin' => 'primary',
            'admin' => 'success',
            'technician' => 'info',
            'viewer' => 'secondary',
        ];
        
        return $colors[$this->role] ?? 'secondary';
    }
}
