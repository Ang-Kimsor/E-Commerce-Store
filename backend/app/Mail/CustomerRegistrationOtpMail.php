<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CustomerRegistrationOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public $otp;
    public $name;

    public function __construct(string $otp, string $name = 'Customer')
    {
        $this->otp = $otp;
        $this->name = $name;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your ' . \App\Models\SiteSetting::get('site_name', 'Unknown Site') . ' Registration Verification Code',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.customer.otp',
            with: [
                'otp' => $this->otp,
                'name' => $this->name,
                'purposeText' => 'registration',
            ],
        );
    }
}

