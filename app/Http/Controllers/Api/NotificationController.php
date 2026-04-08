<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * GET /api/notifications
     * Liste les notifications non lues de l'utilisateur connecté.
     */
    public function index(Request $request): JsonResponse
    {
        $user  = auth()->user();
        $limit = (int) $request->get('limit', 20);

        $notifications = $user->notifications()
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn ($n) => [
                'id'         => $n->id,
                'type'       => $n->data['type']      ?? 'unknown',
                'message'    => $n->data['message']   ?? '',
                'ticket_id'  => $n->data['ticket_id'] ?? null,
                'reference'  => $n->data['reference'] ?? null,
                'priority'   => $n->data['priority']  ?? null,
                'read'       => !is_null($n->read_at),
                'created_at' => $n->created_at,
            ]);

        $unreadCount = $user->unreadNotifications()->count();

        return $this->success([
            'notifications' => $notifications,
            'unread_count'  => $unreadCount,
        ]);
    }

    /**
     * PATCH /api/notifications/{id}/read
     * Marque une notification comme lue.
     */
    public function markRead(string $id): JsonResponse
    {
        $notification = auth()->user()
            ->notifications()
            ->findOrFail($id);

        $notification->markAsRead();

        return $this->success(['unread_count' => auth()->user()->unreadNotifications()->count()]);
    }

    /**
     * PATCH /api/notifications/read-all
     * Marque toutes les notifications comme lues.
     */
    public function markAllRead(): JsonResponse
    {
        auth()->user()->unreadNotifications()->update(['read_at' => now()]);

        return $this->success(['unread_count' => 0], 'Toutes les notifications marquées comme lues.');
    }

    /**
     * DELETE /api/notifications/{id}
     * Supprime une notification.
     */
    public function destroy(string $id): JsonResponse
    {
        auth()->user()->notifications()->findOrFail($id)->delete();

        return $this->success(null, 'Notification supprimée.');
    }
}
