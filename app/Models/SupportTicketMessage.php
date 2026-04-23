<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A single message in a support-ticket thread. Author identity is stored
 * as a snapshot (name/email/id) so the conversation survives user deletion
 * and so tenant-side users can be shown consistently in the super-admin UI
 * without cross-database joins.
 */
class SupportTicketMessage extends Model
{
    protected $connection = 'central';

    public const SCOPE_TENANT      = 'tenant';
    public const SCOPE_SUPER_ADMIN = 'super_admin';

    protected $fillable = [
        'support_ticket_id',
        'author_scope',
        'author_user_id',
        'author_name',
        'author_email',
        'body',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class, 'support_ticket_id');
    }
}
