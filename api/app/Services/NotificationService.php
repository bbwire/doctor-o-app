<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;

class NotificationService
{
    /**
     * Create a notification for a user.
     *
     * @param  array<string, mixed>  $data
     */
    public function createForUser(int $userId, string $type, string $title, ?string $body = null, array $data = []): Notification
    {
        return Notification::create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'data' => $data,
        ]);
    }

    /**
     * Create a notification for each admin user.
     *
     * @param  array<string, mixed>  $data
     */
    public function notifyAdmins(string $type, string $title, ?string $body = null, array $data = []): void
    {
        User::whereIn('role', ['admin', 'super_admin'])->pluck('id')->each(function (int $userId) use ($type, $title, $body, $data): void {
            $this->createForUser($userId, $type, $title, $body, $data);
        });
    }
}
