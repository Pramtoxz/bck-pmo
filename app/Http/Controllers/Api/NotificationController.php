<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Helpers\ApiResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $shop = $request->user();
        $limit = $request->get('limit', 20);

        $notifications = Notification::where('shop_id', $shop->id)
            ->orderBy('created_at', 'desc')
            ->paginate($limit);

        $unreadCount = Notification::where('shop_id', $shop->id)
            ->where('is_read', false)
            ->count();

        return ApiResponse::success([
            'items' => $notifications->map(function($notif) {
                return [
                    'id' => (string) $notif->id,
                    'title' => $notif->title,
                    'message' => $notif->message,
                    'type' => $notif->type,
                    'isRead' => $notif->is_read,
                    'createdAt' => $notif->created_at->format('Y-m-d H:i:s'),
                ];
            }),
            'unreadCount' => $unreadCount
        ]);
    }

    public function markAsRead($id, Request $request)
    {
        $shop = $request->user();
        $notification = Notification::where('shop_id', $shop->id)->findOrFail($id);
        
        $notification->is_read = true;
        $notification->save();

        return ApiResponse::success(null, 'Notification marked as read');
    }
}
