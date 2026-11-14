<?php

namespace App\Domains\User\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'email',
    'otp_hash',
    'attempts',
    'expires_at',
    'verified_at',
    'reset_token_hash',
    'reset_token_expires_at',
    'last_sent_at',
])]
class PasswordResetOtp extends Model
{
    protected function casts(): array
    {
        return [
            'attempts' => 'integer',
            'expires_at' => 'datetime',
            'verified_at' => 'datetime',
            'reset_token_expires_at' => 'datetime',
            'last_sent_at' => 'datetime',
        ];
    }
}