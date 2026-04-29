<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Handoff email — sent at end of build sessions or on the 5:30am Cairo cron
 * so Walid can read progress on his phone at the gym.
 *
 * The digest payload (commits, test counts, features touched, what's next)
 * is assembled by App\Console\Commands\HandoffSendCommand and passed in as
 * structured data so the Blade view can render it cleanly.
 */
class HandoffMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array<string, mixed>  $digest  Structured payload from HandoffSendCommand
     */
    public function __construct(public array $digest) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->digest['subject'] ?? 'YZH HR — build digest',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.handoff',
            with: [
                'digest' => $this->digest,
            ],
        );
    }
}
