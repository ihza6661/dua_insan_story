<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class ActivityLogger
{
    protected ?Request $request;

    public function __construct(?Request $request = null)
    {
        $this->request = $request ?? request();
    }

    /**
     * Log an activity.
     *
     * @param  string  $logType  Type of log (e.g., 'order_cancellation', 'order_status_change')
     * @param  string  $action  Action performed (e.g., 'approved', 'rejected', 'created')
     * @param  Model  $subject  The model being acted upon
     * @param  User|null  $user  The user performing the action
     * @param  string|null  $description  Human-readable description
     * @param  array  $properties  Additional properties (old values, new values, metadata)
     * @return ActivityLog
     */
    public function log(
        string $logType,
        string $action,
        Model $subject,
        ?User $user = null,
        ?string $description = null,
        array $properties = []
    ): ActivityLog {
        return ActivityLog::create([
            'log_type' => $logType,
            'action' => $action,
            'user_id' => $user?->id,
            'user_name' => $user?->full_name,
            'user_role' => $user?->role,
            'subject_type' => get_class($subject),
            'subject_id' => $subject->id,
            'description' => $description ?? $this->generateDescription($logType, $action, $subject, $user),
            'properties' => $properties,
            'ip_address' => $this->request?->ip(),
            'user_agent' => $this->request?->userAgent(),
        ]);
    }

    /**
     * Log order cancellation request creation.
     */
    public function logCancellationRequestCreated(Model $cancellationRequest, User $customer): ActivityLog
    {
        return $this->log(
            logType: 'order_cancellation',
            action: 'created',
            subject: $cancellationRequest,
            user: $customer,
            description: "Customer {$customer->full_name} requested cancellation for order #{$cancellationRequest->order->order_number}",
            properties: [
                'order_id' => $cancellationRequest->order_id,
                'order_number' => $cancellationRequest->order->order_number,
                'cancellation_reason' => $cancellationRequest->cancellation_reason,
                'order_status' => $cancellationRequest->order_status_before,
            ]
        );
    }

    /**
     * Log order cancellation approval.
     */
    public function logCancellationApproved(
        Model $cancellationRequest,
        User $admin,
        ?string $adminNotes = null,
        ?float $refundAmount = null
    ): ActivityLog {
        return $this->log(
            logType: 'order_cancellation',
            action: 'approved',
            subject: $cancellationRequest,
            user: $admin,
            description: "Admin {$admin->full_name} approved cancellation request for order #{$cancellationRequest->order->order_number}",
            properties: [
                'order_id' => $cancellationRequest->order_id,
                'order_number' => $cancellationRequest->order->order_number,
                'customer_name' => $cancellationRequest->customer->full_name,
                'admin_notes' => $adminNotes,
                'refund_amount' => $refundAmount,
                'old_status' => 'pending',
                'new_status' => 'approved',
            ]
        );
    }

    /**
     * Log order cancellation rejection.
     */
    public function logCancellationRejected(
        Model $cancellationRequest,
        User $admin,
        ?string $adminNotes = null
    ): ActivityLog {
        return $this->log(
            logType: 'order_cancellation',
            action: 'rejected',
            subject: $cancellationRequest,
            user: $admin,
            description: "Admin {$admin->full_name} rejected cancellation request for order #{$cancellationRequest->order->order_number}",
            properties: [
                'order_id' => $cancellationRequest->order_id,
                'order_number' => $cancellationRequest->order->order_number,
                'customer_name' => $cancellationRequest->customer->full_name,
                'admin_notes' => $adminNotes,
                'old_status' => 'pending',
                'new_status' => 'rejected',
            ]
        );
    }

    /**
     * Generate a default description based on log type and action.
     */
    protected function generateDescription(
        string $logType,
        string $action,
        Model $subject,
        ?User $user
    ): string {
        $userName = $user?->full_name ?? 'System';
        $subjectType = class_basename($subject);
        $subjectId = $subject->id;
        $type = str_replace('_', ' ', $logType);

        return "{$userName} {$action} {$type} {$subjectType} #{$subjectId}";
    }

    /**
     * Get recent activity logs for a subject.
     */
    public function getActivityForSubject(Model $subject, int $limit = 10)
    {
        return ActivityLog::where('subject_type', get_class($subject))
            ->where('subject_id', $subject->id)
            ->with('user')
            ->latest('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Get recent activity logs by type.
     */
    public function getRecentByType(string $logType, int $limit = 50)
    {
        return ActivityLog::ofType($logType)
            ->with(['user', 'subject'])
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Get activity logs for a user.
     */
    public function getActivityByUser(User $user, int $limit = 50)
    {
        return ActivityLog::byUser($user->id)
            ->with('subject')
            ->latest()
            ->limit($limit)
            ->get();
    }
}
