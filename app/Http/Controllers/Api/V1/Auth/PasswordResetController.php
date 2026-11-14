<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Domains\User\Services\PasswordResetService;
use App\Http\Controllers\Controller;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\VerifyPasswordResetOtpRequest;
use App\Http\Requests\ResetPasswordRequest;
use Illuminate\Http\JsonResponse;

class PasswordResetController extends Controller
{
    public function __construct(
        private PasswordResetService $passwordResetService
    ) {}

    public function requestOtp(ForgotPasswordRequest $request): JsonResponse
        {
            $this->passwordResetService->requestOtp(
                $request->validated('email')
            );

            return response()->json([
                'message' => 'A verification code has been sent to your email address.',
            ]);
        }
        
    public function verifyOtp(
        VerifyPasswordResetOtpRequest $request
    ): JsonResponse {
        $data = $request->validated();

        $resetToken = $this->passwordResetService->verifyOtp(
            $data['email'],
            $data['otp']
        );

        return response()->json([
            'message' => 'OTP verified successfully.',
            'reset_token' => $resetToken,
            'expires_in' => 600,
        ]);
    }

    public function resetPassword(
        ResetPasswordRequest $request
    ): JsonResponse {
        $data = $request->validated();

        $this->passwordResetService->resetPassword(
            $data['email'],
            $data['reset_token'],
            $data['password']
        );

        return response()->json([
            'message' => 'Your password has been reset successfully.',
        ]);
    }
}