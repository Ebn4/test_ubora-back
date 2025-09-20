<?php

namespace App\Services;

use App\Models\User;

interface UserService
{
    public function findByEmail(string $email): User;

    public function findById(string $id): User;

    public function create(string $email, string $cuid, string $profile, string $name): User;
}
