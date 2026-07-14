<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Helpers\ResponseHelper;
use App\Interfaces\AuthRepositoryInterface;
use App\Http\Requests\RegisterStoreRequest;
use App\Http\Requests\LoginStoreRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    protected AuthRepositoryInterface $authRepository;

    /**
     * AuthController constructor.
     *
     * @param AuthRepositoryInterface $authRepository
     */
    public function __construct(AuthRepositoryInterface $authRepository)
    {
        $this->authRepository = $authRepository;
    }

    /**
     * POST /api/register
     */
    public function register(RegisterStoreRequest $request)
    {
        $result = $this->authRepository->register($request->validated());

        return ResponseHelper::jsonResponse(
            true,
            'User registered successfully.',
            [
                'user' => new UserResource($result['user']),
                'token' => $result['token'],
            ],
            201
        );
    }

    /**
     * POST /api/login
     */
    public function login(LoginStoreRequest $request)
    {
        $result = $this->authRepository->login($request->validated());

        return ResponseHelper::jsonResponse(
            true,
            'Login successful.',
            [
                'user' => new UserResource($result['user']),
                'token' => $result['token'],
            ],
            200
        );
    }

    /**
     * POST /api/logout
     */
    public function logout()
    {
        $this->authRepository->logout();

        return ResponseHelper::jsonResponse(
            true,
            'Logged out successfully.',
            null,
            200
        );
    }

    /**
     * GET /api/me
     */
    public function me()
    {
        $user = $this->authRepository->me();

        return ResponseHelper::jsonResponse(
            true,
            'User profile fetched successfully.',
            [
                'user' => new UserResource($user),
            ],
            200
        );
    }
}
