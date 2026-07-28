<?php
return [
    'auth' => [
        'login' => [
            'ip' => [
                'max_attempts' => (int) env('AUTH_RATE_LIMIT_LOGIN_IP_MAX_ATTEMPTS', 20),
                'decay_seconds' => (int) env('AUTH_RATE_LIMIT_LOGIN_IP_DECAY_SECONDS', 60),
            ],
            'failed_credentials' => [
                'max_attempts' => (int) env('AUTH_RATE_LIMIT_FAILED_MAX_ATTEMPTS', 5),
                'decay_seconds' => (int) env('AUTH_RATE_LIMIT_FAILED_DECAY_SECONDS', 60),
            ],
        ],
        'register' => [
            'short_window' => [
                'max_attempts' => (int) env('AUTH_RATE_LIMIT_REGISTER_SHORT_MAX_ATTEMPTS', 3),
                'decay_seconds' => (int) env('AUTH_RATE_LIMIT_REGISTER_SHORT_DECAY_SECONDS', 600),
            ],
            'daily' => [
                'max_attempts' => (int) env('AUTH_RATE_LIMIT_REGISTER_DAILY_MAX_ATTEMPTS', 10),
                'decay_seconds' => (int) env('AUTH_RATE_LIMIT_REGISTER_DAILY_DECAY_SECONDS', 86400),
            ],
        ],
        'refresh' => [
            'max_attempts' => (int) env('AUTH_RATE_LIMIT_REFRESH_MAX_ATTEMPTS', 10),
            'decay_seconds' => (int) env('AUTH_RATE_LIMIT_REFRESH_DECAY_SECONDS', 60),
        ],
        'logout' => [
            'max_attempts' => (int) env('AUTH_RATE_LIMIT_LOGOUT_MAX_ATTEMPTS', 10),
            'decay_seconds' => (int) env('AUTH_RATE_LIMIT_LOGOUT_DECAY_SECONDS', 60),
        ],
        'logout_all' => [
            'max_attempts' => (int) env('AUTH_RATE_LIMIT_LOGOUT_ALL_MAX_ATTEMPTS', 3),
            'decay_seconds' => (int) env('AUTH_RATE_LIMIT_LOGOUT_ALL_DECAY_SECONDS', 300),
        ],
    ],

    'api' => [
        'authenticated' => [
            'max_attempts' => (int) env('AUTH_RATE_LIMIT_API_AUTHENTICATED_MAX_ATTEMPTS', 120),
            'decay_seconds' => (int) env('AUTH_RATE_LIMIT_API_AUTHENTICATED_DECAY_SECONDS', 60),
        ],
    ],
];
