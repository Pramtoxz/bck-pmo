<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CartItem extends Model
{
    use HasFactory;

    protected $table = 'pmov2.cart_items';

    protected $fillable = [
        'cart_id',
        'part_number',
        'qty',
        'price',
        'discount',
        'subtotal',
    ];

    protected $casts = [
        'qty' => 'integer',
        'price' => 'decimal:2',
        'discount' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function cart()
    {
        return $this->belongsTo(Cart::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'part_number', 'part_number');
    }

    public function part()
    {
        return $this->belongsTo(\App\Models\Public\Part::class, 'part_number', 'kd_part');
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($item) {
            $item->subtotal = ($item->price * $item->qty) - $item->discount;
        });
    }
}
