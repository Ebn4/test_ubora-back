<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class LoginController extends Controller
{

    public function login(Request $request)
    {
        if (!Auth::attempt($request->only(["email", "password"]))) {
            return response()->json([
                'status' => false,
                'message' => 'Email ou Mot de passe incorrecte'
            ], 401);
        }

        $user = User::query()->where('email', $request->email)->first();
        $user->token = $user->createToken($user->email)->plainTextToken;

        return response()->json([
            "data" => $user
        ]);

    }

    //fonction pour mettre à jour les information de l'utilissateur daans la BDD
    private function updateUser($user, $ldap, $password)
    {
        if ($user->password == "" || $user->fullname == "" || $user->description == "" || $user->email == "" || $user->msisdn == "") {
            try {

                $udpateduser = User::where('cuid', $user->cuid)
                    ->update([
                        'fullname' => $ldap['FULLNAME'],
                        'description' => $ldap['DESCRIPTION'],
                        'email' => $ldap['EMAIL'],
                        'phonenumber' => str_replace(' ', '', $ldap['PHONENUMBER']),
                        'pass' => Hash::make(Str::random(10))
                    ]);
                Log::info($udpateduser);
                return "User updated";
            } catch (\Throwable $e) {
                Log::error($e->getMessage());
                return "Update-user-error";
            }
        } elseif (!(Hash::check($password, $user->password))) {
            try {
                $udpateduser = User::where('cuid', $user->cuid)
                    ->update([
                        'pass' => Hash::make($password)
                    ]);
                Log::info($udpateduser);
                return "User-password-updated";
            } catch (\Throwable $e) {
                Log::error($e->getMessage());
                return "Update-user-error";
            }
        } else {
            return "Nothing to update";
        }
    }

}
