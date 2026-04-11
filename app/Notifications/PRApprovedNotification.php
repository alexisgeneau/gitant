<?php

namespace App\Notifications;

use App\Models\Bounty;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PRApprovedNotification extends Notification implements ShouldQueue, ShouldBroadcast
{
    use Queueable;

    public function __construct(private readonly Bounty $bounty) {}

    public function via(object $notifiable): array
    {
        $channels = ['database', 'broadcast'];
        if ($this->emailEnabled($notifiable, 'pr_approved')) {
            $channels[] = 'mail';
        }
        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $amountFormatted = number_format($this->bounty->total_amount_cents / 100, 2) . ' EUR';

        return (new MailMessage)
            ->subject("Your PR was approved — {$amountFormatted} incoming!")
            ->greeting("Congratulations!")
            ->line("Your pull request for **{$this->bounty->issue_title}** has been approved.")
            ->line("A payout of **{$amountFormatted}** is being transferred to your bank account.")
            ->action('View Bounty', url("/bounties/{$this->bounty->id}"))
            ->line('Thank you for contributing to open source!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'         => 'pr_approved',
            'bounty_id'    => $this->bounty->id,
            'bounty_title' => $this->bounty->issue_title,
            'amount_cents' => $this->bounty->total_amount_cents,
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
