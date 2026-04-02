<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Super Admin Credentials
    |--------------------------------------------------------------------------
    |
    | Credentials used by the EnsureAdminUsers command to guarantee that
    | super-admin accounts exist after every deployment. Set these values
    | in your .env file — never commit real passwords to version control.
    |
    */

    'users' => [
        [
            'name' => 'Angélica Domingos',
            'email' => 'angelica.domingos@hotmail.com',
            'password' => env('ADMIN_ANGELICA_PASSWORD'),
        ],
        [
            'name' => 'Nando Dev',
            'email' => 'nandinhos@gmail.com',
            'password' => env('ADMIN_NANDO_PASSWORD'),
        ],
    ],

];
