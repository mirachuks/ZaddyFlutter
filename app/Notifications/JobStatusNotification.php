<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class JobStatusNotification extends Notification
{
    protected $title;
    protected $body;

    public function __construct(string $title, string $body)
    {
        $this->title = $title;
        $this->body = $body;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject($this->title)
            ->line($this->body)
            ->line('Open the app to view more details.');
    }

    public function toArray($notifiable)
    {
        return [
            'title' => $this->title,
            'body'  => $this->body,
        ];
    }
}
