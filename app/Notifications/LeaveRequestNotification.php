<?php
namespace App\Notifications;

use App\Models\LeaveRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LeaveRequestNotification extends Notification
{
    use Queueable;
    public function __construct(public LeaveRequest $leaveRequest, public string $event) {}
    public function via(object $notifiable): array { return ['database']; }
    public function toArray(object $notifiable): array
    {
        $name = $this->leaveRequest->user?->name ?? 'Employee';
        $status = ucfirst($this->leaveRequest->status);
        return ['leave_request_id' => $this->leaveRequest->id, 'event' => $this->event,
            'message' => $this->event === 'submitted' ? "$name submitted a leave request." : "Your leave request was $status."];
    }
}
