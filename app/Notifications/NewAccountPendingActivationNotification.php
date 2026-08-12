<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewAccountPendingActivationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 60;

    public array $backoff = [30, 120, 300];

    public function __construct(public User $newUser) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $this->newUser->loadMissing(['organisation', 'province']);

        return (new MailMessage)
            ->subject('Nouveau compte en attente d’activation — AlertBook')
            ->view('emails.new-account-pending-activation-html', [
                'adminName' => $notifiable->name,
                'newUser' => $this->newUser,
                'manageUrl' => url('/users'),
            ]);
    }
}
