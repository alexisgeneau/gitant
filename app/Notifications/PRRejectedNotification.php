<?php

namespace App\Notifications;

use App\Models\Bounty;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PRRejectedNotification extends Notification implements ShouldQueue, ShouldBroadcast
{
    use Queueable;

    public function __construct(private readonly Bounty $bounty) {}

    public function via(object $notifiable): array
    {
        $channels = ['database', 'broadcast'];
        if ($this->emailEnabled($notifiable, 'pr_rejected')) {
            $channels[] = 'mail';
        }
        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("PR rejected: {$this->bounty->issue_title}")
            ->greeting("Update on your bounty")
            ->line("Your pull request for **{$this->bounty->issue_title}** has been rejected by the funder.")
            ->line("You can revise your work and submit a new pull request.")
            ->action('View Bounty', url("/bounties/{$this->bounty->id}"));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'         => 'pr_rejected',
            'bounty_id'    => $this->bounty->id,
            'bounty_title' => $this->bounty->issue_title,
            'url'          => "/bounties/{$this->bounty->id}",
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
