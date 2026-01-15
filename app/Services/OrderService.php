<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\DataPart\SalesOrder;
use App\Models\DataPart\SalesOrderDetail;
use App\Models\Public\Serial;
use App\Models\Public\Part;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function submitOrder($userId)
    {
        return DB::transaction(function () use ($userId) {
            // 1. Ambil cart user
            $cart = Cart::where('user_id', $userId)
                ->where('status', 'active')
                ->with('items.part')
                ->firstOrFail();

            if ($cart->items->isEmpty()) {
                throw new \Exception('Cart is empty');
            }

            // 2. Tentukan jenis order (OIL vs non-OIL)
            $jenisOrder = $this->determineOrderType($cart);

            // 3. Generate nomor SO dengan lock
            $noSo = Serial::generateSO();

            // 4. Hitung grand total
            $grandTotal = $cart->items->sum('subtotal');

            // 5. Insert SO header
            $so = SalesOrder::create([
                'no_so' => $noSo,
                'jenis_so' => $jenisOrder,
                'tgl_so' => now(),
                'jenis_pembayaran' => 'Cash',
                'fk_salesman' => $userId,
                'tipe_source' => 'OTHER',
                'fk_toko' => null, // TODO: get from user->shop
                'tipe_penjualan' => 'Reguler',
                'tgl_jatuh_tempo' => now()->addMonth(),
                'grand_total' => $grandTotal,
                'status_outstanding' => true,
                'status_approve_reject' => 'Waiting For Approval',
            ]);

            // 6. Insert SO detail
            foreach ($cart->items as $item) {
                SalesOrderDetail::create([
                    'fk_so' => $noSo,
                    'fk_part' => $item->part_number,
                    'harga' => $item->price,
                    'qty_so' => $item->qty,
                    'total_harga' => $item->subtotal,
                    'qty_sisa' => $item->qty,
                ]);
            }

            // 7. Clear cart
            $cart->items()->delete();
            $cart->update(['status' => 'checked_out']);

            return [
                'no_so' => $noSo,
                'jenis_so' => $jenisOrder,
                'grand_total' => $grandTotal,
                'status' => 'Waiting For Approval',
            ];
        });
    }

    private function determineOrderType($cart)
    {
        $countOil = 0;
        $countPart = 0;
        $firstItem = null;

        foreach ($cart->items as $item) {
            if (!$firstItem) {
                $firstItem = $item;
            }

            // Ambil part dari public.tblpart_id untuk cek fk_detail_sub_kelompok_part
            $part = Part::where('kd_part', $item->part_number)->first();

            if ($part) {
                // Cek apakah fk_detail_sub_kelompok_part == 'OIL'
                if ($part->fk_detail_sub_kelompok_part == 'OIL') {
                    $countOil++;
                } else {
                    $countPart++;
                }
            } else {
                // Fallback: part tidak ditemukan di AHM, anggap non-OIL
                $countPart++;
            }
        }

        // Logika: mayoritas menentukan
        if ($countPart < $countOil) {
            return 'Oli Regular';  // Lebih banyak OIL
        } elseif ($countPart > $countOil) {
            return 'Other';  // Lebih banyak non-OIL
        } else {
            // Jumlah sama, cek item pertama yang masuk cart
            $firstPart = Part::where('kd_part', $firstItem->part_number)->first();
            
            if ($firstPart && $firstPart->fk_detail_sub_kelompok_part == 'OIL') {
                return 'Oli Regular';
            }
            
            return 'Other';
        }
    }

    public function checkStock($partCode, $bulan = null, $tahun = null)
    {
        $bulan = $bulan ?? date('n');
        $tahun = $tahun ?? date('Y');

        $part = Part::where('kd_part', $partCode)->first();
        
        if (!$part) {
            return [
                'available' => false,
                'message' => 'Part not found',
                'qty' => 0,
            ];
        }

        $stock = $part->getCurrentStock($bulan, $tahun);

        if (!$stock) {
            return [
                'available' => false,
                'message' => 'Stock not found',
                'qty' => 0,
            ];
        }

        $available = $stock->available;

        return [
            'available' => $stock->is_available,
            'message' => $stock->is_available 
                ? "Available {$available} pcs" 
                : 'Not Available',
            'qty' => max(0, $available),
            'qty_on_hand' => $stock->qty_on_hand,
            'qty_booking' => $stock->qty_booking,
            'min_stock' => $part->min_stok,
        ];
    }
}
