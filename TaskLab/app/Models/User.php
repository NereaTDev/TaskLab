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

    protected $fillable = [
        'name',
        'email',
        'password',
        'department',
        'position',
        'user_type',
        'is_admin',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'is_admin'          => 'boolean',
            'is_super_admin'    => 'boolean',
        ];
    }

    public function teams()
    {
        return $this->belongsToMany(Team::class, 'team_user')
            ->withPivot('role')
            ->withTimestamps();
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
        return ! $this->isSuperAdmin() && (bool) ($this->is_admin ?? false);
    }

    public function isStandardUser(): bool
    {
        return ! $this->isSuperAdmin() && ! $this->isAreaAdmin();
    }

    /**
     * Un usuario "tiene equipo" si tiene al menos una asignación de categoría.
     * Los CategoryTypes son los equipos — asignarte a un valor de un tipo = pertenecer a ese equipo.
     */
    public function hasTeam(): bool
    {
        return $this->categoryAssignments()->exists();
    }

    /**
     * IDs de todos los usuarios que comparten al menos un CategoryType con este usuario.
     * Usado para filtrar tareas en el tablero (solo ves tareas de tu mismo "equipo/tipo").
     */
    public function teamMemberIds(): array
    {
        // CategoryType IDs a los que este usuario tiene asignaciones
        $typeIds = \DB::table('user_category_assignments')
            ->join('category_values', 'category_values.id', '=', 'user_category_assignments.category_value_id')
            ->where('user_category_assignments.user_id', $this->id)
            ->pluck('category_values.category_type_id')
            ->unique();

        if ($typeIds->isEmpty()) {
            return [$this->id];
        }

        // Todos los usuarios que tienen asignaciones en esos mismos tipos
        return \DB::table('user_category_assignments')
            ->join('category_values', 'category_values.id', '=', 'user_category_assignments.category_value_id')
            ->whereIn('category_values.category_type_id', $typeIds)
            ->pluck('user_category_assignments.user_id')
            ->unique()
            ->values()
            ->all();
    }
}
