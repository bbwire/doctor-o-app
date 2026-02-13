<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * List notifications for the authenticated user (patient or admin).
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = min((int) $request->get('per_page', 15), 100);
        $unreadOnly = $request->boolean('unread_only');

        $query = Notification::query()
            ->where('user_id', $request->user()->id)
            ->orderByDesc('created_at');

        if ($unreadOnly) {
            $query->whereNull('read_at');
        }

        $notifications = $query->paginate($perPage);

        return NotificationResource::collection($notifications);
    }

    /**
     * Mark a notification as read.
     */
    public function markRead(Request $request, Notification $notification): NotificationResource
    {
        if ($notification->user_id !== $request->user()->id) {
            abort(404);
        }

        $notification->update(['read_at' => $notification->read_at ?? now()]);

        return new NotificationResource($notification);
    }

    /**
     * Mark all notifications as read for the authenticated user.
     */
    public function markAllRead(Request $request): JsonResponse
    {
        Notification::query()
            ->where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['message' => 'All notifications marked as read']);
    }
}
