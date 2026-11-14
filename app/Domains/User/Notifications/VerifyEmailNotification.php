<?php

namespace App\Domains\User\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

class VerifyEmailNotification extends VerifyEmail
{
    public function toMail($notifiable): MailMessage
    {
        $data = [
            'user' => $notifiable,
            'verificationUrl' => $this->verificationUrl($notifiable),
        ];

        return (new MailMessage)
            ->subject('Verify your Stockify email address')
            ->view('emails.auth.verify-email', $data)
            ->text('emails.auth.verify-email-text', $data);
    }
}