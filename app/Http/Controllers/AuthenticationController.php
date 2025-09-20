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
}
