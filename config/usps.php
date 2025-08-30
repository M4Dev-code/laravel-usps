<?php

return [
    // Toggle between USPS Sandbox and Production
    'env' => env('USPS_ENV', 'sandbox'), // 'sandbox' | 'production'

    // Developer Portal (OAuth 2.0) for Labels/Tracking
    'oauth' => [
        'client_id' => env('USPS_CLIENT_ID'),
        'client_secret' => env('USPS_CLIENT_SECRET'),
        'token_url' => env('USPS_TOKEN_URL', 'https://api.usps.com/oauth2/v3/token'),
        'scopes' => env('USPS_SCOPES', 'labels'), // comma-separated, adjust as needed
        'cache_key' => 'usps.oauth.token',
        'timeout' => 10,
    ],

    // API bases (override if USPS changes hosts)
    'base_urls' => [
        'sandbox' => [
            'labels'   => 'https://api-sandbox.usps.com/labels/v3',
            'tracking' => 'https://api-sandbox.usps.com/tracking/v3',
        ],
        'production' => [
            'labels'   => 'https://api.usps.com/labels/v3',
            'tracking' => 'https://api.usps.com/tracking/v3',
        ],
    ],

    // Web Tools XML for rates (use until you move to new price APIs)
    'webtools' => [
        'user_id' => env('USPS_WEBTOOLS_USER_ID'),
        'timeout' => 10,
        'rate_url' => env('USPS_RATE_URL', 'https://secure.shippingapis.com/ShippingAPI.dll'),
    ],

    // Default shipper info for label creation (override per call as needed)
    'shipper' => [
        'name' => env('USPS_SHIPPER_NAME', ''),
        'company' => env('USPS_SHIPPER_COMPANY', ''),
        'phone' => env('USPS_SHIPPER_PHONE', ''),
        'address1' => env('USPS_SHIPPER_ADDRESS1', ''),
        'address2' => env('USPS_SHIPPER_ADDRESS2', ''),
        'city' => env('USPS_SHIPPER_CITY', ''),
        'state' => env('USPS_SHIPPER_STATE', ''),
        'postal_code' => env('USPS_SHIPPER_POSTAL', ''),
        'country' => env('USPS_SHIPPER_COUNTRY', 'US'),
    ],
];
