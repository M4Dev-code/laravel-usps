<?php // src/Services/TrackingService.php

namespace M4dev\UspsShip\Services;

use M4dev\UspsShip\Http\HttpClient;

class TrackingService
{
    public function __construct(protected HttpClient $http, protected array $config) {}

    public function track(string $trackingNumber): array
    {
        return $this->http->get('tracking', '/track/' . urlencode($trackingNumber));
    }
}
