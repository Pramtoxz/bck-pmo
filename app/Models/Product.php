<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $table = 'pmov2.part_images';
    protected $primaryKey = 'part_number';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'part_number',
        'name',
        'description',
        'image',
    ];

    public function part()
    {
        return $this->belongsTo(\App\Models\Public\Part::class, 'part_number', 'kd_part');
    }

    public function cartItems()
    {
        return $this->hasMany(CartItem::class, 'part_number', 'part_number');
    }
}
