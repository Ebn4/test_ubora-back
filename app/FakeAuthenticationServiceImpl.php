<?php

namespace App;

use App\Models\User;
use App\Services\UserService;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

readonly class FakeAuthenticationServiceImpl implements Services\AuthenticationService
{

    public function __construct(
        private UserService $userService
    )
    {

    }

    /**
     * @throws Exception
     */
    public function login(string $cuid, string $password): User
    {
        try {

            $ldapUser = DB::query()->select('*')
                ->from('ldap')
                ->where('cuid', $cuid)
                ->firstOrFail();

            $this->checkPassword($password, $ldapUser->password);
            $user = $this->userService->findByEmail($ldapUser->email);

            $token = $user->createToken($ldapUser->email)->plainTextToken;

            $user->token = $token;
            $user->cuid = $ldapUser->cuid;

            return $user;

        } catch (Exception  $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    public function checkPassword(string $password, string $hash): bool
    {
        if (!Hash::check($password, $hash)) {
            throw new  Exception("The provided credentials are incorrect.");
        }
        return true;
    }

    /**
     * @throws Exception
     */
    public function logout(): void
    {
        try {
            $user = \auth()->user();
            $this->userService->findById($user->id)->tokens()->delete();
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}
