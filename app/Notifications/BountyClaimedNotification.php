<?php

namespace App\Notifications;

use App\Models\Bounty;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BountyClaimedNotification extends Notification implements ShouldQueue, ShouldBroadcast
{
    use Queueable;

    public function __construct(
        private readonly Bounty $bounty,
        private readonly User $hunter,
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['database', 'broadcast'];
        if ($this->emailEnabled($notifiable, 'bounty_claimed')) {
            $channels[] = 'mail';
        }
        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Bounty claimed: {$this->bounty->issue_title}")
            ->greeting("Hello!")
            ->line("@{$this->hunter->username} has claimed your bounty on **{$this->bounty->issue_title}**.")
            ->line("They have 7 days to submit a pull request.")
            ->action('View Bounty', url("/bounties/{$this->bounty->id}"))
            ->line('You will be notified when a PR is submitted.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'       => 'bounty_claimed',
            'bounty_id'  => $this->bounty->id,
            'bounty_title' => $this->bounty->issue_title,
            'hunter'     => $this->hunter->username,
            'url'        => "/bounties/{$this->bounty->id}",
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }

    public function broadcastOn(): array
    {
        return [new \Illuminate\Broadcasting\PrivateChannel("user.{$this->hunter->id}")];
    }

    private function emailEnabled(object $notifiable, string $type): bool
    {
        $prefs = $notifiable->notification_preferences ?? [];
        return $prefs[$type] ?? true;
    }
}
