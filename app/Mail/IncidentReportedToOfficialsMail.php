<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Notifies barangay officials when a resident/citizen reports an incident.
 *
 * IMPORTANT: This mailable deliberately does NOT use `SerializesModels`.
 * We take plain \stdClass snapshots of the incident/tenant/reporter so the
 * queue worker never needs to re-query a tenant database at rehydration
 * time — the worker may be running with no tenant connection configured,
 * and Eloquent model serialization would blow up with "Connection [tenant]
 * not configured" on deserialize.
 *
 * Callers are responsible for constructing these snapshots before dispatch
 * (see `IncidentController::notifyOfficialsIfResidentOrCitizen`).
 */
class IncidentReportedToOfficialsMail extends Mailable
{
    use Queueable;

    public function __construct(
        public \stdClass $incident,
        public \stdClass $tenant,
        public \stdClass $reporter,
        public string $reporterRole,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Incident Reported in ' . ($this->tenant->name ?? 'your barangay'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.incident-reported-to-officials',
            text: 'emails.text.incident-reported-to-officials',
            with: [
                'incident' => $this->incident,
                'tenant' => $this->tenant,
                'reporter' => $this->reporter,
                'reporterRole' => $this->reporterRole,
            ],
        );
    }
}
