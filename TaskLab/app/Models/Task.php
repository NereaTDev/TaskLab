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
        'reporter_id',
        'assignee_id',
        'source',
        'area',
        'estimated_effort',
        'external_message_id',
        'external_channel',
        'external_user_id',
        'external_payload',
    ];

    protected $casts = [
        'requirements'      => 'array',
        'test_cases'        => 'array',
        'external_payload'  => 'array',
    ];

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }
}
