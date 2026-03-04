<?php

namespace App\Models;

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
        'reporter_id',
        'assignee_id',
        'source',
    ];

    protected $casts = [
        'requirements' => 'array',
        'test_cases'   => 'array',
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
