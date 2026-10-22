<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TeamFlowNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $type,
        public string $message,
        public array $data = [],
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => $this->type,
            'message' => $this->message,
            'data' => $this->data,
        ];
    }
}
