<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\CategoryValue;

class Task extends Model
{
    use HasFactory;

    public function categoryValues()
    {
        return $this->belongsToMany(CategoryValue::class, 'task_category_values');
    }

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
        'area',
        'estimated_effort',
        'primary_url',
        'additional_urls',
        'impact',
        'external_message_id',
        'external_channel',
        'external_user_id',
        'external_payload',
        'attachments',
        'done_at',
    ];

    protected $casts = [
        'requirements'      => 'array',
        'test_cases'        => 'array',
        'external_payload'  => 'array',
        'additional_urls'   => 'array',
        'attachments'       => 'array',
        'points'            => 'float',
        'archived_at'       => 'datetime',
        'done_at'           => 'datetime',
    ];

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
