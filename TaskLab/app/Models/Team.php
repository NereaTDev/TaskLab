<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    protected $fillable = ['name', 'slug', 'description'];

    public function users()
    {
        return $this->belongsToMany(User::class, 'team_user')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function admins()
    {
        return $this->belongsToMany(User::class, 'team_user')
            ->withPivot('role')
            ->wherePivot('role', 'admin')
            ->withTimestamps();
    }

    public function members()
    {
        return $this->belongsToMany(User::class, 'team_user')
            ->withPivot('role')
            ->wherePivot('role', 'member')
            ->withTimestamps();
    }
}
