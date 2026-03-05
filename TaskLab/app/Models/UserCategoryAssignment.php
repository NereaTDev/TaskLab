<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserCategoryAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'category_value_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function value()
    {
        return $this->belongsTo(CategoryValue::class, 'category_value_id');
    }
}
