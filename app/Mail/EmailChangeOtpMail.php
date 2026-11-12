<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EmailChangeOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $otp;
    public string $userName;
    public string $purpose;

    public function __construct(string $otp, string $userName, string $purpose = 'current_email_change')
    {
        $this->otp = $otp;
        $this->userName = $userName;
        $this->purpose = $purpose;
    }

    public function envelope(): Envelope
    {
        $subject = match ($this->purpose) {
            'authorize_current_email', 'approve_current_email' => 'EcoCollect Email Change Verification Code',
            default => 'Email Change Verification Code',
        };

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.email-change-otp',
        );
    }
}