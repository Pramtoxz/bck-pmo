<?php

namespace App\Models\DataPart;

use Illuminate\Database\Eloquent\Model;

class SalesOrder extends Model
{
    protected $connection = 'pgsql';
    protected $table = 'data_part.tblso';
    protected $primaryKey = 'no_so';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'no_so',
        'jenis_so',
        'tgl_so',
        'jenis_pembayaran',
        'fk_salesman',
        'tipe_source',
        'fk_toko',
        'tipe_penjualan',
        'tgl_jatuh_tempo',
        'grand_total',
        'status_outstanding',
        'status_approve_reject',
    ];

    protected $casts = [
        'tgl_so' => 'datetime',
        'tgl_jatuh_tempo' => 'datetime',
        'status_outstanding' => 'boolean',
    ];

    public function details()
    {
        return $this->hasMany(SalesOrderDetail::class, 'fk_so', 'no_so');
    }
}
