<?php

namespace App\Mail;

use App\Models\TenantSignupRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TenantSignupRejectedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public TenantSignupRequest $signupRequest)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Tenant Signup Request Update'
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.tenant-signup-rejected'
        );
    }
}
