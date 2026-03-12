<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DiscordMessageBuffer extends Model
{
    protected $table = 'discord_message_buffer';

    protected $fillable = [
        'discord_user_id',
        'channel_id',
        'message_id',
        'message_text',
        'message_url',
        'from_name',
        'from_email',
        'team_name',
        'channel_name',
        'attachments',
        'image_urls',
        'processed_at',
    ];

    protected $casts = [
        'attachments'  => 'array',
        'image_urls'   => 'array',
        'processed_at' => 'datetime',
    ];
}
