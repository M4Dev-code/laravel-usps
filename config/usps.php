<?php

return [
    /*
    |--------------------------------------------------------------------------
    | USPS API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for USPS shipping API integration using OAuth2
    |
    */

    'client_id' => env('USPS_CLIENT_ID'),
    'client_secret' => env('USPS_CLIENT_SECRET'),

    'sandbox' => env('USPS_SANDBOX', true),

    'default_service' => env('USPS_DEFAULT_SERVICE', 'USPS_GROUND_ADVANTAGE'),

    'label_format' => env('USPS_LABEL_FORMAT', 'PDF'),
    'label_type' => env('USPS_LABEL_TYPE', 'SHIPPING_LABEL_ONLY'),

    /*
    |--------------------------------------------------------------------------
    | Default Sender Information
    |--------------------------------------------------------------------------
    |
    | Default sender/return address information for shipments
    |
    */
    'default_sender' => [
        'name' => env('USPS_SENDER_NAME', ''),
        'company' => env('USPS_SENDER_COMPANY', ''),
        'street' => env('USPS_SENDER_STREET', ''),
        'street2' => env('USPS_SENDER_STREET2', ''),
        'city' => env('USPS_SENDER_CITY', ''),
        'state' => env('USPS_SENDER_STATE', ''),
        'zip' => env('USPS_SENDER_ZIP', ''),
        'phone' => env('USPS_SENDER_PHONE', ''),
        'email' => env('USPS_SENDER_EMAIL', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Service Types
    |--------------------------------------------------------------------------
    |
    | Available USPS service types and their configurations
    |
    */
    'services' => [
        'USPS_GROUND_ADVANTAGE' => [
            'name' => 'USPS Ground Advantage',
            'delivery_days' => '2-5',
            'max_weight' => 70,
        ],
        'PRIORITY_MAIL' => [
            'name' => 'Priority Mail',
            'delivery_days' => '1-3',
            'max_weight' => 70,
        ],
        'PRIORITY_MAIL_EXPRESS' => [
            'name' => 'Priority Mail Express',
            'delivery_days' => '1-2',
            'max_weight' => 70,
        ],
        'FIRST_CLASS_MAIL' => [
            'name' => 'First-Class Mail',
            'delivery_days' => '1-5',
            'max_weight' => 15.999,
        ],
        'PARCEL_SELECT' => [
            'name' => 'Parcel Select Ground',
            'delivery_days' => '2-8',
            'max_weight' => 70,
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Caching
    |--------------------------------------------------------------------------
    |
    | Configure caching for API responses
    |
    */
    'cache' => [
        'enabled' => env('USPS_CACHE_ENABLED', true),
        'duration' => env('USPS_CACHE_DURATION', 3600), // 1 hour
        'prefix' => 'usps_',
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Shopping
    |--------------------------------------------------------------------------
    |
    | Configure automatic rate shopping across services
    |
    */
    'rate_shopping' => [
        'enabled' => env('USPS_RATE_SHOPPING', false),
        'services' => ['USPS_GROUND_ADVANTAGE', 'PRIORITY_MAIL', 'PRIORITY_MAIL_EXPRESS'],
        'sort_by' => 'price', // 'price' or 'delivery_time'
    ],
];
