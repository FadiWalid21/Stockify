<?php

namespace App\Domains\User\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordResetOtpNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $otp
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your Stockify password reset code')
            ->view('emails.auth.password-reset-otp', [
                'user' => $notifiable,
                'otp' => $this->otp,
                'expiresIn' => config(
                    'auth.password_reset_otp.expires',
                    5
                ),
            ])
            ->text('emails.auth.password-reset-otp-text', [
                'user' => $notifiable,
                'otp' => $this->otp,
                'expiresIn' => config(
                    'auth.password_reset_otp.expires',
                    5
                ),
            ]);
    }
}