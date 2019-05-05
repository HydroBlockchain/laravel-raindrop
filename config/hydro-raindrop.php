<?php

declare(strict_types=1);

return [

    /*
    |---------------------------------------------------------------------------
    | API Settings
    |---------------------------------------------------------------------------
    |
    | These API credentials can be obtained by signing up for an account
    | at https://hydrogenplatform.com and registering your application.
    |
    */

    'api' => [

        'client_id' => env('HYDRO_RAINDROP_CLIENT_ID', ''),
        'client_secret' => env('HYDRO_RAINDROP_SECRET', ''),
        'application_id' => env('HYDRO_RAINDROP_APPLICATION_ID', ''),
        'environment' => env('HYDRO_RAINDROP_ENVIRONMENT', 'sandbox'),

    ],

    /*
    |---------------------------------------------------------------------------
    | User Model Class
    |---------------------------------------------------------------------------
    |
    | This must be a valid Eloquent Model class.
    |
    */

    'user_model_class' => 'App\\User',

    /*
    |---------------------------------------------------------------------------
    | User Model Table
    |---------------------------------------------------------------------------
    |
    | Table name where the application users are stored.
    |
    */

    'user_model_table' => 'users',

    /*
    |---------------------------------------------------------------------------
    | Login Route
    |---------------------------------------------------------------------------
    |
    | This route will be used to redirect to.
    |
    */

    'login_route_name' => 'login',

    /*
    |---------------------------------------------------------------------------
    | MFA Lifetime (in seconds)
    |---------------------------------------------------------------------------
    |
    | The user has a limited time period to enter the 6 digits from the app
    | into the form. If this time expires the MFA session will be reset and
    | the user will need to re-enter a new set of digits.
    |
    */

    'mfa_lifetime' => 90,

    /*
    |---------------------------------------------------------------------------
    | MFA Verification Lifetime (in minutes)
    |---------------------------------------------------------------------------
    |
    | If the verification lifetime expires. The user must perform MFA again.
    | 0 = eternal.
    |
    */

    'mfa_verification_lifetime' => 0,


    /*
     |--------------------------------------------------------------------------
     | MFA Method
     |--------------------------------------------------------------------------
     |
     | optional = User decides to enable MFA on their account.
     | prompted = MFA setup screen will be prompted after logging in.
     |            User can skip this step and setup MFA later. (default)
     | enforced = MFA is forced site wide. Users will have to setup MFA after
     |            logging in.
     */

    'mfa_method' => 'prompted',

    /*
     |--------------------------------------------------------------------------
     | MFA Maximum Attempts
     |--------------------------------------------------------------------------
     |
     | The user account will be blocked if the number of attempts exceeds this
     | value. (default: 0 = unlimited)
     |
     */

    'mfa_maximum_attempts' => 0,

];
