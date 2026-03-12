<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class TaskImage extends Model
{
    protected $fillable = [
        'task_id',
        'original_name',
        'storage_path',
        'mime_type',
        'size',
    ];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function getUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->storage_path);
    }

    protected $appends = ['url'];
}
