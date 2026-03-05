<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'department',
        'position',
        'user_type',
        'is_admin',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
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
            'is_admin' => 'boolean',
            'is_super_admin' => 'boolean',
        ];
    }

    public function developerProfile()
    {
        return $this->hasOne(DeveloperProfile::class);
    }

    public function categoryAssignments()
    {
        return $this->hasMany(UserCategoryAssignment::class);
    }

    public function categoryValues()
    {
        return $this->belongsToMany(CategoryValue::class, 'user_category_assignments');
    }

    public function isSuperAdmin(): bool
    {
        return (bool) ($this->is_super_admin ?? false);
    }

    public function isAreaAdmin(): bool
    {
        // Admin de área: tiene flag is_admin pero no es super admin
        return ! $this->isSuperAdmin() && (bool) ($this->is_admin ?? false);
    }

    public function isStandardUser(): bool
    {
        return ! $this->isSuperAdmin() && ! $this->isAreaAdmin();
    }
}
