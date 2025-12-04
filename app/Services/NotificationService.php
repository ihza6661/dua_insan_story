<?php

namespace App\Services;

use App\Models\Notification;

class NotificationService
{
    /**
     * Create a notification for a user
     */
    public function create(
        int $userId,
        string $type,
        string $title,
        string $message,
        ?array $data = null
    ): Notification {
        return Notification::create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data,
        ]);
    }

    /**
     * Create order status notification
     */
    public function notifyOrderStatus(
        int $userId,
        int $orderId,
        string $status,
        string $orderNumber
    ): Notification {
        $statusMessages = [
            'pending_payment' => 'Menunggu pembayaran untuk pesanan #' . $orderNumber,
            'paid' => 'Pembayaran berhasil untuk pesanan #' . $orderNumber,
            'processing' => 'Pesanan #' . $orderNumber . ' sedang diproses',
            'shipped' => 'Pesanan #' . $orderNumber . ' telah dikirim',
            'delivered' => 'Pesanan #' . $orderNumber . ' telah sampai',
            'cancelled' => 'Pesanan #' . $orderNumber . ' dibatalkan',
        ];

        $statusTitles = [
            'pending_payment' => 'Menunggu Pembayaran',
            'paid' => 'Pembayaran Berhasil',
            'processing' => 'Pesanan Diproses',
            'shipped' => 'Pesanan Dikirim',
            'delivered' => 'Pesanan Sampai',
            'cancelled' => 'Pesanan Dibatalkan',
        ];

        return $this->create(
            userId: $userId,
            type: Notification::TYPE_ORDER_STATUS,
            title: $statusTitles[$status] ?? 'Update Status Pesanan',
            message: $statusMessages[$status] ?? 'Status pesanan Anda telah diperbarui',
            data: [
                'order_id' => $orderId,
                'order_number' => $orderNumber,
                'status' => $status,
            ]
        );
    }

    /**
     * Create design proof notification
     */
    public function notifyDesignProof(
        int $userId,
        int $designProofId,
        string $action,
        string $orderNumber,
        ?int $orderItemId = null
    ): Notification {
        $actionMessages = [
            'uploaded' => 'Desain baru telah diunggah untuk pesanan #' . $orderNumber,
            'approved' => 'Desain Anda telah disetujui untuk pesanan #' . $orderNumber,
            'revision_requested' => 'Revisi desain diminta untuk pesanan #' . $orderNumber,
            'rejected' => 'Desain ditolak untuk pesanan #' . $orderNumber,
        ];

        $actionTitles = [
            'uploaded' => 'Desain Baru',
            'approved' => 'Desain Disetujui',
            'revision_requested' => 'Revisi Diminta',
            'rejected' => 'Desain Ditolak',
        ];

        return $this->create(
            userId: $userId,
            type: Notification::TYPE_DESIGN_PROOF,
            title: $actionTitles[$action] ?? 'Update Desain',
            message: $actionMessages[$action] ?? 'Ada update pada desain Anda',
            data: [
                'design_proof_id' => $designProofId,
                'order_number' => $orderNumber,
                'order_item_id' => $orderItemId,
                'action' => $action,
            ]
        );
    }

    /**
     * Create payment notification
     */
    public function notifyPayment(
        int $userId,
        int $orderId,
        string $status,
        string $orderNumber
    ): Notification {
        $statusMessages = [
            'pending' => 'Pembayaran untuk pesanan #' . $orderNumber . ' menunggu konfirmasi',
            'success' => 'Pembayaran untuk pesanan #' . $orderNumber . ' berhasil',
            'failed' => 'Pembayaran untuk pesanan #' . $orderNumber . ' gagal',
            'expired' => 'Pembayaran untuk pesanan #' . $orderNumber . ' telah kedaluwarsa',
        ];

        $statusTitles = [
            'pending' => 'Menunggu Pembayaran',
            'success' => 'Pembayaran Berhasil',
            'failed' => 'Pembayaran Gagal',
            'expired' => 'Pembayaran Kedaluwarsa',
        ];

        return $this->create(
            userId: $userId,
            type: Notification::TYPE_PAYMENT,
            title: $statusTitles[$status] ?? 'Update Pembayaran',
            message: $statusMessages[$status] ?? 'Ada update pada pembayaran Anda',
            data: [
                'order_id' => $orderId,
                'order_number' => $orderNumber,
                'payment_status' => $status,
            ]
        );
    }

    /**
     * Create cancellation notification
     */
    public function notifyCancellation(
        int $userId,
        int $orderId,
        string $status,
        string $orderNumber,
        ?string $reason = null
    ): Notification {
        $statusMessages = [
            'pending' => 'Permintaan pembatalan pesanan #' . $orderNumber . ' sedang diproses',
            'approved' => 'Pembatalan pesanan #' . $orderNumber . ' disetujui',
            'rejected' => 'Pembatalan pesanan #' . $orderNumber . ' ditolak',
        ];

        $statusTitles = [
            'pending' => 'Permintaan Pembatalan',
            'approved' => 'Pembatalan Disetujui',
            'rejected' => 'Pembatalan Ditolak',
        ];

        return $this->create(
            userId: $userId,
            type: Notification::TYPE_CANCELLATION,
            title: $statusTitles[$status] ?? 'Update Pembatalan',
            message: $statusMessages[$status] ?? 'Ada update pada pembatalan Anda',
            data: [
                'order_id' => $orderId,
                'order_number' => $orderNumber,
                'cancellation_status' => $status,
                'reason' => $reason,
            ]
        );
    }

    /**
     * Get unread notifications count for a user
     */
    public function getUnreadCount(int $userId): int
    {
        return Notification::where('user_id', $userId)
            ->unread()
            ->count();
    }

    /**
     * Get notifications for a user with pagination
     */
    public function getUserNotifications(int $userId, int $perPage = 20)
    {
        return Notification::where('user_id', $userId)
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(int $notificationId, int $userId): bool
    {
        $notification = Notification::where('id', $notificationId)
            ->where('user_id', $userId)
            ->first();

        if ($notification) {
            $notification->markAsRead();
            return true;
        }

        return false;
    }

    /**
     * Mark all notifications as read for a user
     */
    public function markAllAsRead(int $userId): int
    {
        return Notification::where('user_id', $userId)
            ->unread()
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
    }

    /**
     * Delete old read notifications (cleanup)
     */
    public function deleteOldNotifications(int $days = 90): int
    {
        return Notification::where('is_read', true)
            ->where('read_at', '<', now()->subDays($days))
            ->delete();
    }
}
