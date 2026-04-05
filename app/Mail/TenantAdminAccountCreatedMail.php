<?php

namespace App\Mail;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TenantAdminAccountCreatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $plainPassword;

    public function __construct(
        public Tenant $tenant,
        public User $user,
        string $plainPassword
    ) {
        $this->plainPassword = $plainPassword;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Admin Account Created - ' . $this->tenant->name
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.tenant-admin-account-created',
            text: 'emails.text.tenant-admin-account-created',
            with: [
                'tenant' => $this->tenant,
                'user' => $this->user,
                'plainPassword' => $this->plainPassword,
                'loginUrl' => $this->getLoginUrl(),
            ]
        );
    }

    private function getLoginUrl(): string
    {
        if ($this->tenant->custom_domain) {
            return "https://{$this->tenant->custom_domain}/login";
        }

        if ($this->tenant->subdomain) {
            $baseDomain = config('app.url');
            preg_match('/https?:\/\/(.*?)(?::\d+)?(?:\/|$)/', $baseDomain, $matches);
            $base = $matches[1] ?? 'localhost';
            return "https://{$this->tenant->subdomain}.{$base}/login";
        }

        return config('app.url') . '/login';
    }
}
