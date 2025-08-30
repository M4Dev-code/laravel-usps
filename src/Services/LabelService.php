<?php // src/Services/LabelService.php

namespace m4dev\UspsShip\Services;

use m4dev\UspsShip\Http\HttpClient;

class LabelService
{
    public function __construct(protected HttpClient $http, protected array $config) {}

    /**
     * Create a domestic label.
     * Minimal example: Ground Advantage with basic address details.
     */
    public function createDomestic(array $shipment): array
    {
        $payload = [
            'from' => $shipment['from'] ?? $this->config['shipper'],
            'to'   => $shipment['to'],
            'parcel' => [
                'weight' => ['unit' => 'ounce', 'value' => $shipment['weight_oz']],
                'dimensions' => [
                    'unit' => 'inch',
                    'length' => $shipment['length_in'] ?? 6,
                    'width'  => $shipment['width_in']  ?? 6,
                    'height' => $shipment['height_in'] ?? 6,
                ],
            ],
            'service' => $shipment['service'] ?? 'usps_ground_advantage',
            'options' => $shipment['options'] ?? [], // e.g., signature, insurance
            'reference' => $shipment['reference'] ?? null,
            'label' => ['format' => 'PDF', 'size' => '4x6'],
        ];

        return $this->http->post('labels', '/label', $payload);
    }

    /** Cancel a label by tracking number (if supported by your USPS account). */
    public function cancel(string $trackingNumber): array
    {
        return $this->http->delete('labels', '/label/' . urlencode($trackingNumber));
    }
}
