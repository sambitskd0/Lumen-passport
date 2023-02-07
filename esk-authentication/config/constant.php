<?php
return [
    'SUCCESS_CODE'              =>  200,
    'REQUEST_ERROR_CODE'        => 421,
    'VALIDATION_EXCEPTION_CODE' => 422,
    'DB_EXCEPTION_CODE'         => 423,
    'DATA_TABLE'                => false,
    'OTP_EXPIRYTIME'            => 10,
    'EXPIRATION_CODE'           => 410,
    'viewPrivilege'             => 'view',
    'adminPrivilege'            => 'admin',
    'UNAUTHORIZED_USER'         => 401,
    'PASSPORT'                  => [
        'login_endpoint' => env('PASSPORT_LOGIN_ENDPOINT'),
        'client_id'      => env('PASSPORT_CLIENT_ID'),
        'client_secret'  => env('PASSPORT_CLIENT_SECRET'),
    ],
    'OAUTH2'                  => [
        'login_endpoint' => env('OAUTH2_LOGIN_ENDPOINT'),
        'client_id'      => env('OAUTH2_CLIENT_ID'),
        'client_secret'  => env('OAUTH2_CLIENT_SECRET'),
        'provision_key'  => env('OAUTH2_PROVISION_KEY'),
        'grant_type'     => env('OAUTH2_ACCESS_TOKEN_GRANT_TYPE'),
    ],
];
