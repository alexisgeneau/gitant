<?php

namespace App\Notifications;

use App\Models\Bounty;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PRSubmittedNotification extends Notification implements ShouldQueue, ShouldBroadcast
{
    use Queueable;

    public function __construct(private readonly Bounty $bounty) {}

    public function via(object $notifiable): array
    {
        $channels = ['database', 'broadcast'];
        if ($this->emailEnabled($notifiable, 'pr_submitted')) {
            $channels[] = 'mail';
        }
        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("PR submitted for: {$this->bounty->issue_title}")
            ->greeting("Action required!")
            ->line("A pull request has been submitted for your bounty: **{$this->bounty->issue_title}**.")
            ->line("Please review the PR and approve or request changes within 14 days.")
            ->action('Review PR', url("/bounties/{$this->bounty->id}"))
            ->line('If you take no action within 14 days, the bounty will be auto-approved.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'        => 'pr_submitted',
            'bounty_id'   => $this->bounty->id,
            'bounty_title' => $this->bounty->issue_title,
            'pr_url'      => $this->bounty->linked_pr_url,
            'url'         => "/bounties/{$this->bounty->id}",
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
