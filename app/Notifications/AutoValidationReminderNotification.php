<?php

namespace App\Notifications;

use App\Models\Bounty;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AutoValidationReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Bounty $bounty,
        private readonly int $daysRemaining,
    ) {}

    public function via(object $notifiable): array
    {
        if ($this->emailEnabled($notifiable, 'auto_validation_reminder')) {
            return ['mail', 'database'];
        }
        return ['database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Action required: {$this->daysRemaining} days to review PR")
            ->greeting("Reminder!")
            ->line("You have **{$this->daysRemaining} days** to review the pull request for **{$this->bounty->issue_title}**.")
            ->line("If you take no action, the bounty will be auto-approved and the hunter will receive payment.")
            ->action('Review PR', url("/bounties/{$this->bounty->id}"));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'           => 'auto_validation_reminder',
            'bounty_id'      => $this->bounty->id,
            'bounty_title'   => $this->bounty->issue_title,
            'days_remaining' => $this->daysRemaining,
            'url'            => "/bounties/{$this->bounty->id}",
        ];
    }

    private function emailEnabled(object $notifiable, string $type): bool
    {
        $prefs = $notifiable->notification_preferences ?? [];
        return $prefs[$type] ?? true;
    }
}
