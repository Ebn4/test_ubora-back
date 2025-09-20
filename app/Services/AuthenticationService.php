<?php

namespace App\Services;

use App\Models\User;

interface AuthenticationService
{

    public function login(string $cuid, string $password): User;

    public function logout(): void;

    public function checkPassword(string $password, string $hash): bool;

}
