<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\OrderService;
use App\Helpers\ApiResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    protected $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    public function checkout(Request $request)
    {
        try {
            $user = $request->user();
            $result = $this->orderService->submitOrder($user->id);

            return ApiResponse::success($result, 'Order submitted successfully');
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 400);
        }
    }

    public function checkStock(Request $request, $partCode)
    {
        $bulan = $request->get('bulan');
        $tahun = $request->get('tahun');

        $result = $this->orderService->checkStock($partCode, $bulan, $tahun);

        return ApiResponse::success($result);
    }

    public function history(Request $request)
    {
        $user = $request->user();
        
        $orders = \App\Models\DataPart\SalesOrder::where('fk_salesman', $user->id)
            ->orderBy('tgl_so', 'desc')
            ->limit(50)
            ->get();

        return ApiResponse::success([
            'items' => $orders->map(function($order) {
                return [
                    'id' => $order->no_so,
                    'orderNumber' => $order->no_so,
                    'orderType' => $order->jenis_so,
                    'orderDate' => $order->tgl_so->format('Y-m-d H:i:s'),
                    'grandTotal' => (float) $order->grand_total,
                    'status' => $order->status_approve_reject,
                ];
            })
        ]);
    }

    public function detail(Request $request, $noSo)
    {
        $order = \App\Models\DataPart\SalesOrder::with('details.part')
            ->where('no_so', $noSo)
            ->firstOrFail();

        return ApiResponse::success([
            'orderNumber' => $order->no_so,
            'orderType' => $order->jenis_so,
            'orderDate' => $order->tgl_so->format('Y-m-d H:i:s'),
            'grandTotal' => (float) $order->grand_total,
            'status' => $order->status_approve_reject,
            'items' => $order->details->map(function($detail) {
                return [
                    'partNumber' => $detail->fk_part,
                    'partName' => $detail->part->nm_part ?? '-',
                    'qty' => $detail->qty_so,
                    'price' => (float) $detail->harga,
                    'subtotal' => (float) $detail->total_harga,
                ];
            })
        ]);
    }
}
