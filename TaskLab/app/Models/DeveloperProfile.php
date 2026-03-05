<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeveloperProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'areas',
        'max_parallel_tasks',
        'active',
    ];

    protected $casts = [
        'areas' => 'array',
        'active' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
