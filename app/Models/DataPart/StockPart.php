<?php

namespace App\Models\DataPart;

use Illuminate\Database\Eloquent\Model;

class StockPart extends Model
{
    protected $connection = 'pgsql';
    protected $table = 'data_part.tblstock_part_id';
    public $timestamps = false;

    protected $fillable = [
        'fk_part',
        'qty_on_hand',
        'qty_booking',
        'bulan',
        'tahun',
    ];

    public function part()
    {
        return $this->belongsTo(\App\Models\Public\Part::class, 'fk_part', 'kd_part');
    }

    public function getAvailableAttribute()
    {
        $part = $this->part;
        $minStock = $part ? $part->min_stok : 0;
        return ($this->qty_on_hand - $this->qty_booking) - $minStock;
    }

    public function getIsAvailableAttribute()
    {
        return $this->available >= 1;
    }
}
