<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;


class UserController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {

        $perPage = 10;
        $users = User::query();

        if ($request->has('perPage')) {
            $perPage = $request->input('perPage');
        }

        if ($request->has('search')) {
            $search = $request->input('search');
            $users = $users->whereLike('name', "%$search%")
                ->orWhereLike('email', "%$search%");
        }

        if ($request->has('role')) {
            $role = $request->input('role');
            $users = $users->whereLike('role', "%$role%");
        }

        $users = $users->paginate($perPage);
        return UserResource::collection($users);
    }

    public function show(Request $request)
    {
        return new UserResource($request->user());
    }

    public function destroy(int $id): void
    {
        $user = User::query()->findOrFail($id);
        $user->delete();
    }
}
