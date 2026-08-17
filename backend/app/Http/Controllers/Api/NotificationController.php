<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * نقاط نهاية إشعارات التطبيق (In-App Notifications) لتطبيق الموبايل.
 *
 * Arabic: يوفر قراءة الإشعارات (مع pagination) وعدد غير المقروء وتعليم إشعار/الكل كمقروء.
 * EN: Mobile API endpoints for listing notifications, unread count, and marking read.
 */
class NotificationController extends Controller
{
    /**
     * قائمة إشعارات المستخدم مع عدد غير المقروء.
     * EN: Lists notifications for current user and returns unread count.
     */
    public function index(Request $request): JsonResponse
    {
        $notifications = AppNotification::query()
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(20);

        $unread = AppNotification::query()
            ->where('user_id', $request->user()->id)
            ->unread()
            ->count();

        return response()->json([
            'data' => collect($notifications->items())->map(fn (AppNotification $n) => [
                'id' => $n->id,
                'title' => $n->title,
                'body' => $n->body,
                'type' => $n->type,
                'data' => $n->data,
                'is_read' => $n->read_at !== null,
                'created_at' => $n->created_at?->toIso8601String(),
                'created_at_human' => $n->created_at?->diffForHumans(),
            ])->values()->all(),
            'total' => $notifications->total(),
            'current_page' => $notifications->currentPage(),
            'last_page' => $notifications->lastPage(),
            'unread_count' => $unread,
        ]);
    }

    /**
     * عدد الإشعارات غير المقروءة.
     * EN: Returns unread notifications count.
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $count = AppNotification::query()
            ->where('user_id', $request->user()->id)
            ->unread()
            ->count();

        return response()->json(['count' => $count]);
    }

    /**
     * تعليم إشعار محدد كمقروء.
     * EN: Marks a single notification as read.
     */
    public function markRead(Request $request, int $id): JsonResponse
    {
        AppNotification::query()
            ->where('user_id', $request->user()->id)
            ->whereKey($id)
            ->update(['read_at' => now()]);

        return response()->json(['message' => 'تم تعليم الإشعار كمقروء']);
    }

    /**
     * تعليم جميع الإشعارات كمقروءة.
     * EN: Marks all notifications as read.
     */
    public function readAll(Request $request): JsonResponse
    {
        AppNotification::query()
            ->where('user_id', $request->user()->id)
            ->unread()
            ->update(['read_at' => now()]);

        return response()->json(['message' => 'تم تعليم جميع الإشعارات كمقروءة']);
    }
}
