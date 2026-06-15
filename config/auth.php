<?php

return [

    'defaults' => [
        'guard' => env('AUTH_GUARD', 'web'),
        'passwords' => env('AUTH_PASSWORD_BROKER', 'users'),
    ],

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],
        'student' => [
            'driver' => 'sanctum',
            'provider' => 'students',
        ],
        'teacher' => [
            'driver' => 'sanctum',
            'provider' => 'teachers',
        ],
        'admin' => [
            'driver' => 'sanctum',
            'provider' => 'admins',
        ],
        'supervisor' => [
    'driver' => 'sanctum',
    'provider' => 'supervisors',
           ],
        'accountant' => [
        'driver' => 'sanctum',
        'provider' => 'accountants',
    ],
        'guardian' => [
        'driver' => 'sanctum',
        'provider' => 'guardians',
    ],

    ],

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => App\Models\User::class,
        ],
        'students' => [
            'driver' => 'eloquent',
            'model' => App\Models\Student::class,
        ],
        'teachers' => [
            'driver' => 'eloquent',
            'model' => App\Models\Teacher::class,
        ],
        'admins' => [
            'driver' => 'eloquent',
            'model' => App\Models\Admin::class,
        ],
        'supervisors' => [
    'driver' => 'eloquent',
    'model' => App\Models\Supervisor::class,
            ],
          'accountants' => [
        'driver' => 'eloquent',
        'model' => App\Models\Accountant::class,
    ],
        'guardians' => [
        'driver' => 'eloquent',
        'model' => App\Models\Guardian::class,
    ],

    ],

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => env('AUTH_PASSWORD_RESET_TOKEN_TABLE', 'password_reset_tokens'),
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),

];
