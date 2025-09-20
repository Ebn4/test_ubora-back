<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\AuthenticationServiceProvider::class,
    App\Providers\FileServiceProvider::class,
    App\Providers\UserServiceProvider::class,
    App\Providers\UserLdapServiceProvider::class,
    App\Providers\EvaluatorServiceProvider::class,
    \ZanySoft\Zip\ZipServiceProvider::class
];
