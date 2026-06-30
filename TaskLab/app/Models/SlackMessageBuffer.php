<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SlackMessageBuffer extends Model
{
    protected $table = 'slack_message_buffer';

    protected $fillable = [
        'slack_user_id',
        'channel_id',
        'message_id',
        'message_text',
        'message_url',
        'from_name',
        'from_email',
        'workspace_id',
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
