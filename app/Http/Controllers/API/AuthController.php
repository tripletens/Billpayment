<?php

namespace App\Http\Controllers\API;

use App\DTOs\Auth\LoginUserDTO;
use App\DTOs\Auth\RegisterUserDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    private array $response = [];

    public function __construct(
        protected AuthService $authService
    ) {
        $this->response = [
            'status' => false,
            'code' => 500,
            'message' => 'An unexpected error occurred.',
        ];
    }

    public function user(Request $request)
    {
        return $this->success('User profile retrieved successfully.', 200, $request->user());
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        $dto = new RegisterUserDTO(
            $request->name,
            $request->email,
            $request->password,
            $request->phone
        );

        $result = $this->authService->register($dto);

        return response()->json([
            'status' => true,
            'code' => 201,
            'message' => 'User registered successfully.',
            'data' => $result,
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $dto = new LoginUserDTO(
            $request->email,
            $request->password
        );

        $result = $this->authService->login($dto);

        return response()->json([
            'status' => true,
            'code' => 200,
            'message' => 'Login successful.',
            'data' => $result,
        ]);
    }

    public function forgotPassword(\App\Http\Requests\Auth\ForgotPasswordRequest $request): JsonResponse
    {
        $dto = new \App\DTOs\Auth\ForgotPasswordDTO($request->email);

        $this->authService->forgotPassword($dto);

        return response()->json([
            'status' => true,
            'code' => 200,
            'message' => 'Reset link sent.',
        ]);
    }

    public function resetPassword(\App\Http\Requests\Auth\ResetPasswordRequest $request): JsonResponse
    {
        $dto = new \App\DTOs\Auth\ResetPasswordDTO(
            $request->email,
            $request->token,
            $request->password
        );

        $this->authService->resetPassword($dto);

        return response()->json([
            'status' => true,
            'code' => 200,
            'message' => 'Password reset successfully.',
        ]);
    }
}
