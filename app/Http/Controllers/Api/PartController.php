<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Public\Part;
use App\Helpers\ApiResponse;
use Illuminate\Http\Request;

class PartController extends Controller
{
    public function index(Request $request)
    {
        $bulan = date('n');
        $tahun = date('Y');
        
        $query = Part::query();

        // Filter: hanya part aktif dan harga > 0
        $query->where('part_active', true)
              ->where('het', '>', 0);

        // Filter: hanya part yang stock ready
        $query->whereExists(function($q) use ($bulan, $tahun) {
            $q->select(\DB::raw(1))
              ->from('data_part.tblstock_part_id')
              ->whereColumn('data_part.tblstock_part_id.fk_part', 'public.tblpart_id.kd_part')
              ->where('data_part.tblstock_part_id.bulan', $bulan)
              ->where('data_part.tblstock_part_id.tahun', $tahun)
              ->whereRaw('(data_part.tblstock_part_id.qty_on_hand - data_part.tblstock_part_id.qty_booking - public.tblpart_id.min_stok) >= 1');
        });

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('kd_part', 'ILIKE', "%{$search}%")
                  ->orWhere('nm_part', 'ILIKE', "%{$search}%");
            });
        }

        // Filter by category
        if ($request->has('category')) {
            $query->where('fk_detail_sub_kelompok_part', $request->category);
        }

        // Sort
        $sortBy = $request->get('sortBy', 'nm_part');
        $order = $request->get('order', 'asc');
        $query->orderBy($sortBy, $order);

        // Pagination
        $limit = $request->get('limit', 20);
        $parts = $query->paginate($limit);

        return ApiResponse::success([
            'items' => $parts->map(function($part) {
                $partImage = Product::where('part_number', $part->kd_part)->first();
                $stock = $part->getCurrentStock();
                
                $imageUrl = 'https://ik.imagekit.io/zlt25mb52fx/ahmcdn/uploads/hgp/thumbnail/fungsi-cairan-pendingin-coolant-image.png';
                if ($partImage && $partImage->image) {
                    $imageUrl = url($partImage->image);
                }
                
                // Prioritas: part_images.name > public.tblpart_id.nm_part
                $name = $part->nm_part ?: '-';
                if ($partImage && $partImage->name) {
                    $name = $partImage->name;
                }
                
                // Prioritas: part_images.description > part name
                $description = $name;
                if ($partImage && $partImage->description) {
                    $description = $partImage->description;
                }
                
                return [
                    'id' => (string) $part->kd_part,
                    'image' => $imageUrl,
                    'partNumber' => $part->kd_part,
                    'name' => $name,
                    'description' => $description,
                    'price' => (float) $part->het,
                    'isReady' => true, // Pasti true karena sudah difilter
                ];
            }),
            'pagination' => [
                'page' => $parts->currentPage(),
                'limit' => $parts->perPage(),
                'total' => $parts->total(),
                'totalPages' => $parts->lastPage(),
            ]
        ]);
    }

    public function show($partNumber)
    {
        $part = Part::where('kd_part', $partNumber)->firstOrFail();
        $partImage = Product::where('part_number', $part->kd_part)->first();
        $stock = $part->getCurrentStock();

        $imageUrl = 'https://ik.imagekit.io/zlt25mb52fx/ahmcdn/uploads/hgp/thumbnail/fungsi-cairan-pendingin-coolant-image.png';
        if ($partImage && $partImage->image) {
            $imageUrl = url('storage/' . $partImage->image);
        }

        // Prioritas: part_images.name > public.tblpart_id.nm_part
        $name = $part->nm_part ?: '-';
        if ($partImage && $partImage->name) {
            $name = $partImage->name;
        }
        
        // Prioritas: part_images.description > part name
        $description = $name;
        if ($partImage && $partImage->description) {
            $description = $partImage->description;
        }

        return ApiResponse::success([
            'id' => (string) $part->kd_part,
            'image' => $imageUrl,
            'partNumber' => $part->kd_part,
            'name' => $name,
            'description' => $description,
            'price' => (float) $part->het,
            'isReady' => $stock ? $stock->is_available : false,
            'stock' => $stock ? max(0, $stock->available) : 0,
            'category' => $part->fk_detail_sub_kelompok_part,
        ]);
    }
}
