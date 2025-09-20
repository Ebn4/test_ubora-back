<?php

namespace App\Http\Controllers;

use App\Enums\RoleEnum;
use Illuminate\Http\Request;

class HasAdminRoleController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();
        $hasAdminRole = false;
        if ($user->role == RoleEnum::ADMIN->value) {
            $hasAdminRole = true;
        }

        return response()
            ->json([
                "hasAdminRole" => $hasAdminRole
            ]);
    }

}
