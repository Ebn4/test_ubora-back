<?php

return [

    /*
    |--------------------------------------------------------------------------
    | LDAP API URL
    |--------------------------------------------------------------------------
    |
    | This value is the base URL of the LDAP API that the application will
    | use to perform LDAP operations such as searching for users and
    | retrieving user details by CUID.
    |
    */

    'api_url' => env('LDAP_API_URL', 'https://default-ldap-api-url.com'),
    'endpoints' => [
        'authenticate' => '/ldap',
        'generate_otp' => '/generate',
        'verify_otp' => '/check',
        'mail' => '/mail',
        'sms' => '/sms',
    ],
    'timeout' => 10,
    'retry' => 3,
];
