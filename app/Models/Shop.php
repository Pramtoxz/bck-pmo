<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Sanctum\HasApiTokens;

class Shop extends Model
{
    use HasFactory, HasApiTokens;

    protected $table = 'pmov2.shops';

    protected $fillable = [
        'user_id',
        'code',
        'name',
        'phone',
        'address',
        'city',
        'province',
        'ref_toko_id',
        'is_active',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'password' => 'hashed',
    ];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function carts()
    {
        return $this->hasMany(Cart::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function activeCart()
    {
        return $this->hasOne(Cart::class)->where('status', 'active');
    }
}
