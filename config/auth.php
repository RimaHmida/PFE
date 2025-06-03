<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Defaults de l'Authentification
    |--------------------------------------------------------------------------
    |
    | Ces options définissent le guard d'authentification par défaut et le
    | broker pour le reset du mot de passe. Vous pouvez les modifier selon vos besoins.
    |
    */

    'defaults' => [
        'guard' => env('AUTH_GUARD', 'web'),
        'passwords' => env('AUTH_PASSWORD_BROKER', 'users'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Guards d'Authentification
    |--------------------------------------------------------------------------
    |
    | Vous pouvez définir ici tous les guards pour votre application. Le guard 'web'
    | utilise le driver "session" pour la gestion classique par cookies. Le guard 'api'
    | ci-dessous utilise le driver "jwt" pour une authentification stateless par token.
    |
    | Le guard 'api' sera utilisé pour protéger vos endpoints d'API.
    |
    */

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],

        'api' => [
            'driver' => 'jwt',
            'provider' => 'users',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Providers d'Utilisateur
    |--------------------------------------------------------------------------
    |
    | Ici, vous pouvez configurer comment les utilisateurs sont récupérés de votre base
    | de données. La configuration par défaut utilise Eloquent et le modèle User.
    |
    */

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => env('AUTH_MODEL', App\Models\User::class),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Réinitialisation des mots de passe
    |--------------------------------------------------------------------------
    |
    | Ces options définissent le comportement du reset des mots de passe, y compris
    | la table utilisée pour stocker les tokens et le provider utilisateur correspondant.
    |
    */

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => env('AUTH_PASSWORD_RESET_TOKEN_TABLE', 'password_reset_tokens'),
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Timeout pour la confirmation de mot de passe
    |--------------------------------------------------------------------------
    |
    | Définissez le délai avant que l'utilisateur ait besoin de reconfirmer son mot de passe.
    |
    */

    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),

];
