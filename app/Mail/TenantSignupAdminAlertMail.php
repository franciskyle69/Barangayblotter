<?php

namespace App\Mail;

use App\Models\TenantSignupRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TenantSignupAdminAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public TenantSignupRequest $signupRequest)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Tenant Signup Request Pending Review'
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.tenant-signup-admin-alert',
            text: 'emails.text.tenant-signup-admin-alert'
        );
    }
}
