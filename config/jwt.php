<?php

return [

    /*
    |--------------------------------------------------------------------------
    | JWT Authentication Secret
    |--------------------------------------------------------------------------
    | Générer avec : php artisan jwt:secret
    */
    'secret' => env('JWT_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | JWT Authentication Keys
    |--------------------------------------------------------------------------
    | Pour RS256 — laisser null si on utilise HS256 (recommandé pour démarrer)
    */
    'keys' => [
        'public'  => env('JWT_PUBLIC_KEY'),
        'private' => env('JWT_PRIVATE_KEY'),
        'passphrase' => env('JWT_PASSPHRASE'),
    ],

    /*
    |--------------------------------------------------------------------------
    | JWT time to live (en minutes)
    |--------------------------------------------------------------------------
    | 60 = 1 heure. Mettre null pour ne pas expirer.
    */
    'ttl' => env('JWT_TTL', 60),

    /*
    |--------------------------------------------------------------------------
    | Refresh Time To Live (en minutes)
    |--------------------------------------------------------------------------
    | 20160 = 14 jours
    */
    'refresh_ttl' => env('JWT_REFRESH_TTL', 20160),

    /*
    |--------------------------------------------------------------------------
    | JWT hashing algorithm
    |--------------------------------------------------------------------------
    | Algorithmes supportés : HS256, HS384, HS512, RS256, RS384, RS512
    */
    'algo' => env('JWT_ALGO', Tymon\JWTAuth\Providers\JWT\Lcobucci::class),

    /*
    |--------------------------------------------------------------------------
    | Required Claims
    |--------------------------------------------------------------------------
    */
    'required_claims' => [
        'iss', 'iat', 'exp', 'nbf', 'sub', 'jti',
    ],

    /*
    |--------------------------------------------------------------------------
    | Persistent Claims
    |--------------------------------------------------------------------------
    | Ces claims sont copiés dans le token rafraîchi.
    */
    'persistent_claims' => [
        'role', 'name',
    ],

    /*
    |--------------------------------------------------------------------------
    | Lock Subject
    |--------------------------------------------------------------------------
    */
    'lock_subject' => true,

    /*
    |--------------------------------------------------------------------------
    | Leeway
    |--------------------------------------------------------------------------
    | Tolérance en secondes pour l'expiration (pour les décalages d'horloge).
    */
    'leeway' => env('JWT_LEEWAY', 0),

    /*
    |--------------------------------------------------------------------------
    | Blacklist Enabled
    |--------------------------------------------------------------------------
    | Si activé, les tokens invalidés (logout) sont mis en liste noire.
    */
    'blacklist_enabled' => env('JWT_BLACKLIST_ENABLED', true),

    'blacklist_grace_period' => env('JWT_BLACKLIST_GRACE_PERIOD', 0),

    'show_black_list_exception' => env('JWT_SHOW_BLACKLIST_EXCEPTION', false),

    /*
    |--------------------------------------------------------------------------
    | Decrypt Keys
    |--------------------------------------------------------------------------
    */
    'decrypt_keys' => true,

    /*
    |--------------------------------------------------------------------------
    | Providers
    |--------------------------------------------------------------------------
    */
    'providers' => [
        'jwt'   => Tymon\JWTAuth\Providers\JWT\Lcobucci::class,
        'auth'  => Tymon\JWTAuth\Providers\Auth\Illuminate::class,
        'storage' => Tymon\JWTAuth\Providers\Storage\Illuminate::class,
    ],
];
