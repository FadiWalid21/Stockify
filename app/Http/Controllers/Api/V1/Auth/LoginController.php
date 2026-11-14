<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Domains\User\Services\LoginService;
use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use Illuminate\Http\JsonResponse;

class LoginController extends Controller
{
    public function __construct(
        private LoginService $loginService
    ) {}

    public function __invoke(LoginRequest $request): JsonResponse
    {
        $result = $this->loginService->login(
            $request->validated()
        );

        return response()->json([
            'message' => 'Login successful.',
            'user' => [
                'id' => $result['user']->id,
                'name' => $result['user']->name,
                'email' => $result['user']->email,
                'company_id' => $result['user']->company_id,
            ],
            'token' => $result['token'],
        ]);
    }
}