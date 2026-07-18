<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class TenantInvitationNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $tenantName,
        private readonly string $roleLabel,
        private readonly string $acceptUrl,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Convite para '.$this->tenantName)
            ->greeting('Olá!')
            ->line('Você foi convidado para participar de '.$this->tenantName.' como '.$this->roleLabel.'.')
            ->action('Aceitar convite', $this->acceptUrl)
            ->line('O convite expira em 7 dias.');
    }
}
