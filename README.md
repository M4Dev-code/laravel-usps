# laravel-usps
Laravel package to get USPS rates and create/cancel/download shipping labels and tracking shipments
# laravel-usps

## Install

```bash
composer require M4Dev-code/laravel-usps
php artisan vendor:publish --tag=config --provider="Acme\\UspsShip\\UspsServiceProvider"
```


Set env vars in `.env`:

```
USPS_ENV=sandbox
USPS_CLIENT_ID=your_client_id
USPS_CLIENT_SECRET=your_client_secret
USPS_TOKEN_URL=https://api.usps.com/oauth2/v3/token
USPS_SCOPES=labels tracking
USPS_WEBTOOLS_USER_ID=xxxxxxxxxxxx
```

## Rates (temporary via Web Tools)

```php
use Acme\UspsShip\Services\RateService;

$rates = app(RateService::class)->domestic([
    'weight_oz' => 12,
    'zip_orig' => '94103',
    'zip_dest' => '10001',
    'size' => 'REGULAR',
    'container' => '',
    'machinable' => true,
]);
```

## Create a domestic label (Developer Portal)

```php
use Acme\UspsShip\Services\LabelService;

$label = app(LabelService::class)->createDomestic([
  'to' => [
    'name' => 'Jane Doe',
    'address1' => '350 5th Ave',
    'city' => 'New York', 'state' => 'NY', 'postal_code' => '10118', 'country' => 'US'
  ],
  'weight_oz' => 12,
  'service' => 'usps_ground_advantage',
  'reference' => 'ORDER-1001'
]);

// $label typically includes: trackingNumber, labelUrl / labelBase64
```

## Cancel a label

```php
app(LabelService::class)->cancel('9400...');
```

## Track a package

```php
use Acme\UspsShip\Services\TrackingService;

$events = app(TrackingService::class)->track('9400...');
```

### Notes

- Production label purchasing/cancelation requires proper USPS account permissions (e.g., eVS/USPS Ship enrollment).
- Keep Web Tools only for rates until USPS exposes modern pricing endpoints you can adopt; plan to remove XML later.
- Map your service codes to USPS service types as returned by the API.

````

---

## 11) Optional: simple controller examples (drop into your app)

```php
<?php // app/Http/Controllers/ShippingController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Acme\UspsShip\Services\{RateService, LabelService, TrackingService};

class ShippingController extends Controller
{
    public function rates(Request $request, RateService $rates)
    {
        return response()->json($rates->domestic([
            'weight_oz' => (int) $request->input('weight_oz', 16),
            'zip_orig' => config('usps.shipper.postal_code'),
            'zip_dest' => $request->input('zip'),
            'size' => 'REGULAR',
            'container' => '',
            'machinable' => true,
        ]));
    }

    public function label(Request $request, LabelService $labels)
    {
        $data = $labels->createDomestic([
            'to' => $request->input('to'),
            'weight_oz' => (int) $request->input('weight_oz', 16),
            'service' => $request->input('service', 'usps_ground_advantage'),
            'reference' => $request->input('reference'),
        ]);
        return response()->json($data);
    }

    public function track(string $tracking, TrackingService $tracking)
    {
        return response()->json($tracking->track($tracking));
    }
}
````
