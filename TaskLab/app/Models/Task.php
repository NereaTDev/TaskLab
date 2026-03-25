<?php

namespace App\Models;

use App\Enums\TaskStatus;
use App\Enums\TaskType;
use App\Enums\TaskPriority;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description_raw',
        'description_ai',
        'requirements',
        'behavior',
        'test_cases',
        'type',
        'status',
        'priority',
        'points',
        'reporter_id',
        'assignee_id',
        'source',
        'primary_url',
        'additional_urls',
        'impact',
        'external_message_id',
        'external_channel',
        'external_user_id',
        'attachments',
        'done_at',
        'ai_refined_at',
        'rejection_reasons',
        'co_requester_ids',
    ];

    protected $casts = [
        'requirements'      => 'array',
        'test_cases'        => 'array',
        'additional_urls'   => 'array',
        'attachments'       => 'array',
        'rejection_reasons' => 'array',
        'co_requester_ids'  => 'array',
        'points'            => 'float',
        'archived_at'       => 'datetime',
        'done_at'           => 'datetime',
        'ai_refined_at'     => 'datetime',
    ];

    // ─── Relations ────────────────────────────────────────────────────────────

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function taskImages()
    {
        return $this->hasMany(\App\Models\TaskImage::class);
    }
}
