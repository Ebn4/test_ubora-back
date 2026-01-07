<?php

namespace App\Services;

interface UserLdapService
{

    public function authenticate(string $cuid, string $password);

    public function generateOtp(string $cuid);

    public function verifyOtp(string $cuid, string $otp): bool;

    // public function searchUser(string $search);

    // public function findUserByCuid(string $cuid);

}
