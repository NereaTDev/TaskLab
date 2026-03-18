<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GithubConnection extends Model
{
    protected $fillable = [
        'owner',
        'repo',
        'token',
        'branch',
        'active',
        'file_tree',
        'last_synced_at',
    ];

    protected $casts = [
        'token'          => 'encrypted',
        'active'         => 'boolean',
        'file_tree'      => 'array',
        'last_synced_at' => 'datetime',
    ];

    /** Returns the active connection, if any. */
    public static function active(): ?self
    {
        return static::where('active', true)->first();
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->owner}/{$this->repo}";
    }
}
