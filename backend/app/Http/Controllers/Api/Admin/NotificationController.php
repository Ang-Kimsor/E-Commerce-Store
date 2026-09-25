<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    /**
     * Get all notifications for the authenticated admin user.
     */
    public function index(Request $request)
    {
        $notifications = $request->user()->notifications()->paginate(20);

        $data = $notifications->map(function ($notification) {
            $array = $notification->toArray();
            $array['read_at'] = $notification->pivot->read_at;
            unset($array['pivot']);
            return $array;
        });

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
                'unread_count' => $request->user()->unreadNotifications()->count(),
            ]
        ]);
    }

    /**
     * Mark a specific notification as read.
     */
    public function markAsRead(Request $request, string $id)
    {
        $request->user()->notifications()->updateExistingPivot($id, ['read_at' => now()]);

        return response()->json(['message' => 'Notification marked as read.']);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllRead(Request $request)
    {
        DB::table('notification_user')
            ->where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['message' => 'All notifications marked as read.']);
    }
}
