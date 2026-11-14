<?php

namespace App\Domains\User\Services;

use App\Domains\User\Models\PasswordResetOtp;
use App\Domains\User\Models\User;
use App\Domains\User\Notifications\PasswordResetOtpNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

class PasswordResetService
{
    public function requestOtp(string $email): void
    {
        $user = User::query()
            ->where('email', $email)
            ->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'email' => ['No account exists with this email address.'],
            ]);
        }

        $existingOtp = PasswordResetOtp::query()
            ->where('email', $email)
            ->first();

        $throttleSeconds = config(
            'auth.password_reset_otp.throttle',
            60
        );

        // Prevent requesting OTPs too frequently.
        if (
            $existingOtp &&
            $existingOtp->last_sent_at &&
            $existingOtp->last_sent_at->gt(
                now()->subSeconds($throttleSeconds)
            )
        ) {
            $retryAfter = max(
                1,
                (int) now()->diffInSeconds(
                    $existingOtp->last_sent_at->copy()->addSeconds($throttleSeconds),
                    false
                )
            );

            throw new TooManyRequestsHttpException(
                $retryAfter,
                "Please wait {$retryAfter} seconds before requesting another OTP."
            );
        }

        $otp = (string) random_int(100000, 999999);

        DB::transaction(function () use ($email, $otp) {
            PasswordResetOtp::updateOrCreate(
                ['email' => $email],
                [
                    'otp_hash' => Hash::make($otp),
                    'attempts' => 0,
                    'expires_at' => now()->addMinutes(
                        config('auth.password_reset_otp.expires', 5)
                    ),
                    'verified_at' => null,
                    'reset_token_hash' => null,
                    'reset_token_expires_at' => null,
                    'last_sent_at' => now(),
                ]
            );
        });

        $user->notify(new PasswordResetOtpNotification($otp));
    }

    public function verifyOtp(string $email, string $otp): string
    {
        $resetToken = DB::transaction(function () use ($email, $otp) {
            $resetOtp = PasswordResetOtp::query()
                ->where('email', $email)
                ->lockForUpdate()
                ->first();

            if (! $resetOtp || $resetOtp->expires_at->isPast()) {
                return null;
            }

            if ($resetOtp->verified_at !== null) {
                return null;
            }

            $maxAttempts = config(
                'auth.password_reset_otp.max_attempts',
                5
            );

            if ($resetOtp->attempts >= $maxAttempts) {
                return null;
            }

            if (! Hash::check($otp, $resetOtp->otp_hash)) {
                $resetOtp->increment('attempts');

                return null;
            }

            $token = Str::random(64);

            $resetOtp->update([
                'verified_at' => now(),
                'reset_token_hash' => Hash::make($token),
                'reset_token_expires_at' => now()->addMinutes(10),
            ]);

            return $token;
        });

        if ($resetToken === null) {
            throw ValidationException::withMessages([
                'otp' => ['The OTP is invalid, expired, or has already been used.'],
            ]);
        }

        return $resetToken;
    }

    public function resetPassword(
        string $email,
        string $resetToken,
        string $newPassword
    ): void {
        $reset = DB::transaction(function () use (
            $email,
            $resetToken,
            $newPassword
        ) {
            $resetOtp = PasswordResetOtp::query()
                ->where('email', $email)
                ->lockForUpdate()
                ->first();

            if (
                ! $resetOtp ||
                $resetOtp->verified_at === null ||
                $resetOtp->reset_token_hash === null ||
                $resetOtp->reset_token_expires_at === null ||
                $resetOtp->reset_token_expires_at->isPast() ||
                ! Hash::check($resetToken, $resetOtp->reset_token_hash)
            ) {
                return false;
            }

            $user = User::query()
                ->where('email', $email)
                ->first();

            if (! $user) {
                return false;
            }

            $user->update([
                'password' => $newPassword,
            ]);

            // Revoke all existing Sanctum tokens for this user.
            $user->tokens()->delete();

            $resetOtp->delete();

            return true;
        });

        if (! $reset) {
            throw ValidationException::withMessages([
                'reset_token' => ['The password reset authorization is invalid or expired.'],
            ]);
        }
    }
}
