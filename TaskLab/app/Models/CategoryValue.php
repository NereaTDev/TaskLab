<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CategoryValue extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_type_id',
        'name',
        'slug',
        'parent_id',
        'sort_order',
    ];

    public function type()
    {
        return $this->belongsTo(CategoryType::class, 'category_type_id');
    }

    public function parent()
    {
        return $this->belongsTo(CategoryValue::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(CategoryValue::class, 'parent_id');
    }
}
