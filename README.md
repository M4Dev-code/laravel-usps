# USPS Laravel Package

A comprehensive Laravel package for USPS shipping integration with support for address validation, rate calculation, label creation, and package tracking using the USPS API v3 with OAuth2 authentication.

## Features

- **OAuth2 Authentication**: Secure authentication using client credentials
- **Address Validation**: Validate and standardize addresses using USPS API v3
- **Rate Calculation**: Get shipping rates for different USPS services with caching support
- **Label Creation**: Generate shipping labels in PDF format with tracking numbers
- **Package Tracking**: Track packages and automatically update shipment status
- **Rate Caching**: Cache rates to reduce API calls and improve performance
- **Rate Shopping**: Compare rates across multiple USPS services
- **Database Integration**: Store shipments and tracking information
- **Artisan Commands**: Built-in commands for testing and maintenance

## Requirements

- PHP 8.1 or higher
- Laravel 10.0 or 11.0
- cURL extension
- JSON extension
- Valid USPS Developer Account

## Installation

1. Install via Composer:

```bash
composer require your-vendor/usps-laravel
```

2. Publish the configuration file:

```bash
php artisan vendor:publish --tag=usps-config
```

3. Run the migrations:

```bash
php artisan migrate
```

4. Configure your environment variables in `.env`:

```env
# USPS API Credentials (from USPS Developer Portal)
USPS_CLIENT_ID=your_client_id_from_usps_developer_portal
USPS_CLIENT_SECRET=your_client_secret_from_usps_developer_portal
USPS_SANDBOX=true

# Optional configurations
USPS_DEFAULT_SERVICE=USPS_GROUND_ADVANTAGE
USPS_LABEL_FORMAT=PDF
USPS_CACHE_ENABLED=true
USPS_RATE_SHOPPING=false

# Default sender information
USPS_SENDER_NAME="Your Company"
USPS_SENDER_COMPANY="Your Company Inc"
USPS_SENDER_STREET="123 Main St"
USPS_SENDER_CITY="Anytown"
USPS_SENDER_STATE="CA"
USPS_SENDER_ZIP="12345"
USPS_SENDER_PHONE="555-123-4567"
USPS_SENDER_EMAIL="shipping@yourcompany.com"
```

## Getting USPS API Credentials

1. Go to the [USPS Developer Portal](https://developer.usps.com)
2. Create an account or log in
3. Register your application
4. You'll receive a `client_id` and `client_secret`
5. Use these credentials in your `.env` file

**Note**: The modern USPS API v3 uses OAuth2 authentication with client credentials, not API keys.

## Quick Start

### Basic Usage Example

```php
use UspsShipping\Laravel\Services\UspsShippingService;

$shippingService = app(UspsShippingService::class);

$shipment = $shippingService->createShipment([
    'from_address' => [
        'street' => '123 Sender St',
        'city' => 'Los Angeles',
        'state' => 'CA',
        'zip' => '90210'
    ],
    'to_address' => [
        'street' => '456 Recipient Ave',
        'city' => 'New York',
        'state' => 'NY',
        'zip' => '10001'
    ],
    'weight' => 2.5,
    'dimensions' => [
        'length' => 12,
        'width' => 9,
        'height' => 3
    ],
    'service_type' => 'PRIORITY_MAIL',
    'customer_name' => 'John Doe',
    'customer_email' => 'john@example.com'
]);

echo "Tracking Number: " . $shipment->tracking_number;
echo "Label URL: " . $shipment->label_url;
```

## Detailed Usage

### Address Validation

```php
use UspsShipping\Laravel\Services\AddressValidationService;

$addressService = app(AddressValidationService::class);

$result = $addressService->validate([
    'street' => '123 Main Street',
    'city' => 'Beverly Hills',
    'state' => 'CA',
    'zip' => '90210'
]);

if ($result['valid']) {
    $standardizedAddress = $result['standardized'];
    $corrections = $result['corrections'];

    foreach ($corrections as $field => $correction) {
        echo "Corrected {$field}: {$correction['original']} → {$correction['corrected']}\n";
    }
}
```

### Getting Shipping Rates

```php
use UspsShipping\Laravel\Services\RateService;

$rateService = app(RateService::class);

// Get rates for a single service
$rates = $rateService->getRates([
    'origin_zip' => '90210',
    'destination_zip' => '10001',
    'weight' => 2.5,
    'length' => 12,
    'width' => 9,
    'height' => 3,
    'mail_class' => 'PRIORITY_MAIL'
]);

echo "Shipping cost: $" . $rates['totalBasePrice'];

// Enable rate shopping in config to compare multiple services
config(['usps.rate_shopping.enabled' => true]);
$allRates = $rateService->getRates([
    'origin_zip' => '90210',
    'destination_zip' => '10001',
    'weight' => 2.5
]);

foreach ($allRates['rates'] as $rate) {
    echo "{$rate['service_name']}: $" . $rate['totalBasePrice'] . "\n";
}
```

### Creating Shipping Labels

```php
use UspsShipping\Laravel\Services\LabelService;

$labelService = app(LabelService::class);

$shipment = $labelService->createAndSaveLabel([
    'to_address' => [
        'street' => '123 Customer St',
        'city' => 'New York',
        'state' => 'NY',
        'zip' => '10001'
    ],
    'weight' => 2.5,
    'dimensions' => [
        'length' => 12,
        'width' => 9,
        'height' => 3
    ],
    'service_type' => 'PRIORITY_MAIL',
    'customer_name' => 'John Doe',
    'customer_email' => 'john@example.com',
    'label_format' => 'PDF'
]);

// Access the label
echo "Tracking Number: " . $shipment->tracking_number;
echo "Label URL: " . $shipment->label_url;

// Download label as base64 for storage
if ($shipment->label_base64) {
    file_put_contents("label_{$shipment->tracking_number}.pdf",
        base64_decode($shipment->label_base64));
}
```

### Package Tracking

```php
use UspsShipping\Laravel\Services\TrackingService;

$trackingService = app(TrackingService::class);

$trackingInfo = $trackingService->track('9400111899562516386781');

echo "Status: " . $trackingInfo['status'] . "\n";
echo "Current Location: " . ($trackingInfo['current_location']['city'] ?? 'Unknown') . "\n";

foreach ($trackingInfo['events'] as $event) {
    echo $event['datetime'] . ": " . $event['description'] . " - " .
         ($event['location']['city'] ?? 'Unknown location') . "\n";
}

// Update a specific shipment's tracking
$updated = $trackingService->updateShipmentTracking('9400111899562516386781');
if ($updated) {
    echo "Shipment tracking updated successfully";
}
```

### Using the Facade

```php
use Usps;

// Validate address
$address = Usps::validateAddress([
    'street' => '123 Main St',
    'city' => 'Beverly Hills',
    'state' => 'CA',
    'zip' => '90210'
]);

// Get rates
$rates = Usps::getRates([
    'origin_zip' => '90210',
    'destination_zip' => '10001',
    'weight' => 2.5
]);

// Track package
$tracking = Usps::trackPackage('9400111899562516386781');

// Get ZIP code info
$zipInfo = Usps::getZipInfo('90210');
```

## Artisan Commands

### Test USPS Connection

```bash
php artisan usps:test-connection
```

### Update Tracking Information

```bash
# Update all non-delivered shipments
php artisan usps:update-tracking --all

# Update specific tracking numbers
php artisan usps:update-tracking --tracking=9400111899562516386781 --tracking=9400111899562516386782

# Update shipments from last 7 days
php artisan usps:update-tracking --days=7
```

### Clean Up Expired Cache

```bash
php artisan usps:cleanup-cache
```

## Configuration Options

### Service Types

Configure available USPS services in `config/usps.php`:

```php
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
    // ... more services
],
```

### Rate Shopping

Enable automatic rate comparison:

```php
'rate_shopping' => [
    'enabled' => true,
    'services' => ['USPS_GROUND_ADVANTAGE', 'PRIORITY_MAIL', 'PRIORITY_MAIL_EXPRESS'],
    'sort_by' => 'price', // 'price' or 'delivery_time'
],
```

### Caching

Configure rate caching:

```php
'cache' => [
    'enabled' => true,
    'duration' => 3600, // 1 hour
    'prefix' => 'usps_',
],
```

## Working with Models

### UspsShipment Model

```php
use UspsShipping\Laravel\Models\UspsShipment;

// Get all shipments
$shipments = UspsShipment::all();

// Get shipments by status
$inTransit = UspsShipment::where('status', 'in_transit')->get();

// Get delivered shipments
$delivered = UspsShipment::where('status', 'delivered')->get();

// Check shipment status
$shipment = UspsShipment::where('tracking_number', '9400111899562516386781')->first();
if ($shipment->isDelivered()) {
    echo "Package delivered on: " . $shipment->delivered_at;
}

// Get formatted weight and service name
echo "Weight: " . $shipment->formatted_weight;
echo "Service: " . $shipment->service_name;
```

## Advanced Usage

### Batch Operations

```php
use UspsShipping\Laravel\Services\UspsShippingService;

$shippingService = app(UspsShippingService::class);

$shipments = [
    [
        'to_address' => ['street' => '123 First St', 'city' => 'New York', 'state' => 'NY', 'zip' => '10001'],
        'weight' => 1.5,
        'service_type' => 'PRIORITY_MAIL'
    ],
    [
        'to_address' => ['street' => '456 Second Ave', 'city' => 'Chicago', 'state' => 'IL', 'zip' => '60601'],
        'weight' => 2.0,
        'service_type' => 'USPS_GROUND_ADVANTAGE'
    ]
];

$results = $shippingService->createBatchShipments($shipments);

foreach ($results as $index => $result) {
    if ($result['success']) {
        echo "Shipment {$index}: " . $result['shipment']->tracking_number . "\n";
    } else {
        echo "Shipment {$index} failed: " . $result['error'] . "\n";
    }
}
```

### Custom Exception Handling

```php
use UspsShipping\Laravel\Exceptions\{
    UspsException,
    UspsApiException,
    UspsValidationException,
    UspsAuthenticationException,
    UspsRateLimitException
};

try {
    $rates = $rateService->getRates($params);
} catch (UspsAuthenticationException $e) {
    // Handle authentication errors (invalid client credentials)
    Log::error('USPS Authentication Error: ' . $e->getMessage());
} catch (UspsValidationException $e) {
    // Handle address validation errors
    return response()->json(['error' => 'Invalid address: ' . $e->getMessage()], 400);
} catch (UspsRateLimitException $e) {
    // Handle rate limiting
    return response()->json(['error' => 'Rate limit exceeded'], 429);
} catch (UspsApiException $e) {
    // Handle other API errors
    Log::error('USPS API Error: ' . $e->getMessage(), $e->getContext());
} catch (UspsException $e) {
    // Handle general USPS errors
    Log::error('USPS Error: ' . $e->getMessage());
}
```

### Controller Example

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use UspsShipping\Laravel\Services\{RateService, LabelService, TrackingService, AddressValidationService};
use UspsShipping\Laravel\Models\UspsShipment;

class ShippingController extends Controller
{
    public function calculateRates(Request $request, RateService $rateService)
    {
        $request->validate([
            'origin_zip' => 'required|string|size:5',
            'destination_zip' => 'required|string|size:5',
            'weight' => 'required|numeric|min:0.1|max:70',
            'length' => 'nullable|numeric',
            'width' => 'nullable|numeric',
            'height' => 'nullable|numeric',
        ]);

        try {
            $rates = $rateService->getRates($request->all());

            return response()->json([
                'success' => true,
                'rates' => $rates
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }

    public function createShipment(Request $request, LabelService $labelService)
    {
        $request->validate([
            'to_address.street' => 'required|string',
            'to_address.city' => 'required|string',
            'to_address.state' => 'required|string|size:2',
            'to_address.zip' => 'required|string|size:5',
            'weight' => 'required|numeric|min:0.1|max:70',
            'service_type' => 'required|string',
        ]);

        try {
            $shipment = $labelService->createAndSaveLabel($request->all());

            return response()->json([
                'success' => true,
                'shipment' => $shipment,
                'tracking_url' => "https://tools.usps.com/go/TrackConfirmAction?tLabels={$shipment->tracking_number}"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }

    public function trackShipment(string $trackingNumber, TrackingService $trackingService)
    {
        try {
            $trackingInfo = $trackingService->track($trackingNumber);

            return response()->json([
                'success' => true,
                'tracking' => $trackingInfo
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }

    public function validateAddress(Request $request, AddressValidationService $addressService)
    {
        $request->validate([
            'street' => 'required|string',
            'city' => 'required|string',
            'state' => 'required|string|size:2',
            'zip' => 'required|string|size:5',
        ]);

        $result = $addressService->validate($request->all());

        return response()->json([
            'success' => $result['valid'],
            'result' => $result
        ]);
    }

    public function getShipments(Request $request)
    {
        $shipments = UspsShipment::query()
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->service_type, fn($q) => $q->where('service_type', $request->service_type))
            ->latest()
            ->paginate(20);

        return response()->json($shipments);
    }

    public function getShipment(string $trackingNumber)
    {
        $shipment = UspsShipment::where('tracking_number', $trackingNumber)->first();

        if (!$shipment) {
            return response()->json(['error' => 'Shipment not found'], 404);
        }

        return response()->json($shipment);
    }
}
```

## Testing

Run the test suite:

```bash
vendor/bin/phpunit
```

### Environment Setup for Testing

Create a `.env.testing` file:

```env
USPS_CLIENT_ID=test_client_id
USPS_CLIENT_SECRET=test_client_secret
USPS_SANDBOX=true
```

## Scheduled Tasks

Add to your `app/Console/Kernel.php` to automatically update tracking:

```php
protected function schedule(Schedule $schedule)
{
    // Update tracking for recent shipments every hour
    $schedule->command('usps:update-tracking --days=7')
             ->hourly()
             ->withoutOverlapping();

    // Clean up expired cache daily
    $schedule->command('usps:cleanup-cache')
             ->daily();
}
```

## Error Handling Best Practices

1. **Always validate addresses** before creating labels
2. **Handle rate limits** with proper delays between requests
3. **Cache rates** to reduce API calls
4. **Log errors** for debugging and monitoring
5. **Use try-catch blocks** around all USPS API calls

## API Rate Limits

The USPS API has rate limits. The package includes:

- Automatic delays between batch operations
- OAuth token caching to reduce auth requests
- Rate caching to minimize API calls

## Troubleshooting

### Common Issues

1. **Authentication Errors**: Verify your client_id and client_secret are correct
2. **Address Validation Failures**: Ensure addresses are properly formatted
3. **Rate Errors**: Check weight and dimension limits for selected service
4. **Label Creation Failures**: Verify all required address fields are provided

### Debug Mode

Enable debug logging in your `.env`:

```env
LOG_LEVEL=debug
```

## Support

- [USPS Developer Portal](https://developer.usps.com)
- [USPS API Documentation](https://developer.usps.com/apis)

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This package is open-sourced software licensed under the [MIT license]
(LICENSE.md).

## Changelog

### v1.0.0

- Initial release with USPS API v3 support
- OAuth2 authentication
- Address validation, rates, labels, and tracking
- Rate caching and shopping features
- Comprehensive test suite
