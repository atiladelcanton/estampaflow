<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

final class TenantWelcomeMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly string $userName,
        public readonly string $tenantName,
        public readonly string $loginEmail,
        public readonly string $tenantUrl,
        public readonly string $loginUrl,
        public readonly string $passwordResetUrl,
        public readonly string $trialEndsAt,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Bem-vindo ao EstampaFlow — '.$this->tenantName,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.tenancy.welcome',
            text: 'emails.tenancy.welcome-text',
        );
    }

    /**
     * @return array<int, mixed>
     */
    public function attachments(): array
    {
        return [];
    }
}
