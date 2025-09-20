<?php

namespace App;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FakeUserLdapServiceImpl implements \App\Services\UserLdapService
{

    public function searchUser(string $search): Collection
    {
        return DB::table('ldap')
            ->where('email', $search)
            ->orWhere('cuid', 'LIKE', "%$search%")
            ->orWhere('name', 'LIKE', "%$search%")
            ->get();
    }

    public function findUserByCuid(string $cuid)
    {
        return DB::table('ldap')
            ->where('cuid', $cuid)
            ->firstOrFail();
    }
}
