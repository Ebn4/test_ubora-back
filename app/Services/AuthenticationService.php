<?php

namespace App\Services;


interface AuthenticationService
{

    public function login(string $cuid, string $password);

    public function logout(): void;

    public function verifyOtp(string $cuid, string $otp): bool;

}
