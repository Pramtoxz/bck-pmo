<?php

namespace App\Models\Public;

use Illuminate\Database\Eloquent\Model;

class Serial extends Model
{
    protected $connection = 'pgsql';
    protected $table = 'public.tblserial';
    public $timestamps = false;

    protected $fillable = [
        'name',
        'counter',
        'last_date',
    ];

    protected $casts = [
        'counter' => 'integer',
        'last_date' => 'datetime',
    ];

    public static function generateSO()
    {
        return \DB::transaction(function () {
            $serial = self::where('name', 'POD-PD')
                ->lockForUpdate()
                ->first();

            if (!$serial) {
                $serial = self::create([
                    'name' => 'POD-PD',
                    'counter' => 0,
                    'last_date' => now(),
                ]);
            }

            $tahun = date('Y');
            $newCounter = $serial->counter + 1;
            $nomax = sprintf('%06d', $newCounter);
            $no_so = "$tahun/$nomax/POD-PD";

            $serial->update([
                'counter' => $newCounter,
                'last_date' => now(),
            ]);

            return $no_so;
        });
    }
}
