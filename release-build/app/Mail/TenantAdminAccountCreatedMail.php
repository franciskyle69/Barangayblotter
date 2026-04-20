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
        if ($this->shouldUseLocalLvhDomain()) {
            $subdomain = $this->tenant->subdomain ?: $this->tenant->slug;

            if ($subdomain) {
                return "http://{$subdomain}.lvh.me:8000/login";
            }

            return 'http://lvh.me:8000/login';
        }

        if ($this->tenant->custom_domain || $this->tenant->subdomain) {
            return rtrim($this->tenant->getUrl(), '/') . '/login';
        }

        return rtrim(config('app.url'), '/') . '/login';
    }

    private function shouldUseLocalLvhDomain(): bool
    {
        $host = parse_url((string) config('app.url'), PHP_URL_HOST) ?: '';

        return in_array($host, ['localhost', '127.0.0.1'], true);
    }
}
