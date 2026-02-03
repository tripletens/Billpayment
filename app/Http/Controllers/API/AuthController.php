<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\DTOs\Auth\RegisterUserDTO;
use App\DTOs\Auth\LoginUserDTO;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    public function __construct(
        protected AuthService $authService
    ) {}

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
            'message' => 'Password reset successfully.',
        ]);
    }
}
