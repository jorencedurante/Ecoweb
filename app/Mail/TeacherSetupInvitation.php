<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TeacherSetupInvitation extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $teacherName,
        public string $setupUrl,
        public int $expiresInHours,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Set up your EcoCollect teacher account',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.teacher-setup-invitation',
        );
    }
}
