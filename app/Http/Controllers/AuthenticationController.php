<?php

namespace App\Http\Controllers;

use App\Http\Requests\AuthenticationRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthenticationService;
use Illuminate\Http\Exceptions\HttpResponseException;
use \Exception;

class AuthenticationController extends Controller
{
    public function __construct(private AuthenticationService $authenticationService)
    {
    }

    /**
     * Handle the incoming request.
     */
    public function login(AuthenticationRequest $request)
    {
        try {
            $user = $this->authenticationService->login($request->cuid, $request->password);
            return new UserResource($user);
        } catch (Exception $e) {
            return throw new HttpResponseException(
                response()->json(data: [
                    "errors" => $e->getMessage()
                ], status: 400)
            );
        }
    }

    public function logout()
    {
        try {
            $this->authenticationService->logout();
        } catch (Exception  $e) {
            return throw new HttpResponseException(
                response()->json(data: [
                    "errors" => $e->getMessage()
                ], status: 400)
            );
        }
    }

    public function resendOtp(Request $request)
    {
        $request->validate([
            'cuid' => 'required|string'
        ]);

        try {
            // Vérifier si les données LDAP sont toujours en cache
            $ldapUser = Cache::get("ldap_user_{$request->cuid}");

            if (!$ldapUser) {
                return response()->json([
                    'success' => false,
                    'error' => 'Session expirée. Veuillez vous reconnecter.'
                ], 401);
            }

            // Renvoyer l'OTP
            $this->authenticationService->resendOtp($request->cuid);

            return response()->json([
                'success' => true,
                'message' => 'Un nouveau code a été envoyé à votre numéro de téléphone.'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }


}
