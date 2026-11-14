<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Domains\Company\Services\RegisterCompanyService;
use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterCompanyRequest;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;

class RegisterController extends Controller
{
    public function __construct(
        private RegisterCompanyService $registerCompanyService
    ) {}

    public function __invoke(
        RegisterCompanyRequest $request
    ): JsonResponse {
        $data = $request->validated();

        $companyData = [
            'name' => $data['company_name'],
            'email' => $data['company_email'] ?? null,
            'phone' => $data['company_phone'] ?? null,
            'address' => $data['company_address'] ?? null,
        ];

        $userData = [
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
        ];

        $user = $this->registerCompanyService->register(
            $companyData,
            $userData
        );

        // Send the email verification notification.
        event(new Registered($user));

        return response()->json([
            'message' => 'Company registered successfully. Please verify your email.',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'company_id' => $user->company_id,
            ],
        ], 201);
    }
}
