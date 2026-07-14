<?php

namespace App\Interfaces;

interface AuthRepositoryInterface
{
    /**
     * Register a new user and generate a token.
     *
     * @param array $data
     * @return array Contains 'user' and 'token'
     */
    public function register(array $data): array;

    /**
     * Authenticate user credentials and generate a token.
     *
     * @param array $credentials
     * @return array Contains 'user' and 'token'
     */
    public function login(array $credentials): array;

    /**
     * Revoke the current user's token.
     *
     * @return void
     */
    public function logout(): void;

    /**
     * Get the currently authenticated user.
     *
     * @return mixed
     */
    public function me();

    /**
     * Update user profile.
     *
     * @param array $data
     * @return mixed
     */
    public function updateProfile(array $data);
}
