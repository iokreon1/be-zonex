<?php

namespace App\Repositories;

use App\Models\User;
use App\Interfaces\AuthRepositoryInterface;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthRepository implements AuthRepositoryInterface
{
    /**
     * Register a new user and generate a token.
     *
     * @param array $data
     * @return array Contains 'user' and 'token'
     */
    public function register(array $data): array
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone_number' => $data['phone_number'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'],
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    /**
     * Authenticate user credentials and generate a token.
     *
     * @param array $credentials
     * @return array Contains 'user' and 'token'
     * @throws ValidationException
     */
    public function login(array $credentials): array
    {
        $user = User::where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Kredensial yang diberikan tidak cocok dengan data kami.'],
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    /**
     * Revoke the current user's token.
     *
     * @return void
     */
    public function logout(): void
    {
        if (auth()->user()) {
            auth()->user()->currentAccessToken()->delete();
        }
    }

    /**
     * Get the currently authenticated user.
     *
     * @return mixed
     */
    public function me()
    {
        return auth()->user();
    }

    /**
     * Update user profile.
     *
     * @param array $data
     * @return mixed
     */
    public function updateProfile(array $data)
    {
        // Placeholder untuk fungsionalitas di masa mendatang
        return null;
    }
}
