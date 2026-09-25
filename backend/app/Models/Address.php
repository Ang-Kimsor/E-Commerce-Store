<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Order;
use App\Models\User;

class Address extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'customer_id',
        'label',
        'name',
        'phone',
        'address_line_1',
        'address_line_2',
        'village',
        'commune',
        'district',
        'province',
        'notes',
        'is_default',
        'latitude',
        'longitude',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    protected $appends = ['full_address'];

    // Get full address as string
    public function getFullAddressAttribute()
    {
        $parts = array_filter([
            $this->address_line_1,
            $this->address_line_2,
            $this->village,
            $this->commune,
            $this->district,
            $this->province,
        ]);
        
        return implode(', ', $parts);
    }

    // Check if address has coordinates
    public function hasCoordinates()
    {
        return !is_null($this->latitude) && !is_null($this->longitude);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
