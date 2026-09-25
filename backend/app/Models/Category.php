<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_active',
    ];

    protected $appends = ['status'];

    protected static function booted(): void
    {
    }

    public function getStatusAttribute()
    {
        if (method_exists($this, 'trashed') && $this->trashed()) {
            return 'deleted';
        }
        return $this->is_active ? 'active' : 'inactive';
    }


    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
