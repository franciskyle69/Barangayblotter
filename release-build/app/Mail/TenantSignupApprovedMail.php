<?php

namespace App\Mail;

use App\Models\Tenant;
use App\Models\TenantSignupRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TenantSignupApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public TenantSignupRequest $signupRequest,
        public Tenant $tenant
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Tenant Signup Request Approved'
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.tenant-signup-approved',
            text: 'emails.text.tenant-signup-approved'
        );
    }
}
