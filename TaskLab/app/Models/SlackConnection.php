<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SlackConnection extends Model
{
    protected $fillable = [
        'workspace_id',
        'workspace_name',
        'bot_token',
        'signing_secret',
        'channel_ids',
        'active',
    ];

    protected $casts = [
        'bot_token'      => 'encrypted',
        'signing_secret' => 'encrypted',
        'channel_ids'    => 'array',
        'active'         => 'boolean',
    ];

    public static function active(): ?self
    {
        return static::where('active', true)->first();
    }
}
