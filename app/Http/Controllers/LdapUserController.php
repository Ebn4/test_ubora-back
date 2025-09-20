<?php

namespace App\Http\Controllers;

use App\Http\Resources\LdapUserResource;
use App\Services\UserLdapService;
use Illuminate\Http\Request;

class LdapUserController extends Controller
{

    public function __construct(
        private UserLdapService $userLdapService
    )
    {

    }

    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $users = $this->userLdapService->searchUser($request->user);
        return LdapUserResource::collection($users);
    }
}
