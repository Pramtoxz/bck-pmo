<?php

namespace App\Models\Public;

use Illuminate\Database\Eloquent\Model;

class PartCategory extends Model
{
    protected $connection = 'pgsql';
    protected $table = 'public.tbldetail_sub_kelompok_part_id';
    protected $primaryKey = 'kd_detail_sub_kelompok_part';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'kd_detail_sub_kelompok_part',
        'detail_sub_kelompok_part',
    ];
}
