<?php

namespace App\Models\DataPart;

use Illuminate\Database\Eloquent\Model;

class SalesOrderDetail extends Model
{
    protected $connection = 'pgsql';
    protected $table = 'data_part.tblso_detail';
    public $timestamps = false;

    protected $fillable = [
        'fk_so',
        'fk_part',
        'harga',
        'qty_so',
        'total_harga',
        'qty_sisa',
    ];

    protected $casts = [
        'harga' => 'decimal:2',
        'total_harga' => 'decimal:2',
    ];

    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class, 'fk_so', 'no_so');
    }

    public function part()
    {
        return $this->belongsTo(\App\Models\Public\Part::class, 'fk_part', 'kd_part');
    }
}
