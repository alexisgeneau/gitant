<?php

namespace App\Notifications;

use App\Models\Bounty;
use App\Models\Dispute;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DisputeOpenedNotification extends Notification implements ShouldQueue, ShouldBroadcast
{
    use Queueable;

    public function __construct(
        private readonly Dispute $dispute,
        private readonly Bounty $bounty,
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['database', 'broadcast'];
        if ($this->emailEnabled($notifiable, 'dispute_opened')) {
            $channels[] = 'mail';
        }
        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Dispute opened: {$this->bounty->issue_title}")
            ->greeting("Dispute notification")
            ->line("A dispute has been opened for the bounty **{$this->bounty->issue_title}**.")
            ->line("You have 7 days to respond to the dispute.")
            ->action('View Dispute', url("/disputes/{$this->dispute->id}"))
            ->line('If you do not respond, the dispute will be ruled in favour of the opener.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'         => 'dispute_opened',
            'dispute_id'   => $this->dispute->id,
            'bounty_id'    => $this->bounty->id,
            'bounty_title' => $this->bounty->issue_title,
            'url'          => "/disputes/{$this->dispute->id}",
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }

    public function broadcastOn(): array
    {
        return [new \Illuminate\Broadcasting\PrivateChannel("user.{$notifiable->id}")];
    }

    private function emailEnabled(object $notifiable, string $type): bool
    {
        $prefs = $notifiable->notification_preferences ?? [];
        return $prefs[$type] ?? true;
    }
}
