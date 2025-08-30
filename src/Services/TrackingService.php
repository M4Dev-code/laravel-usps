<?php

namespace UspsShipping\Laravel\Services;

use UspsShipping\Laravel\UspsClient;
use UspsShipping\Laravel\Models\UspsShipment;
use Carbon\Carbon;

class TrackingService
{
    protected UspsClient $client;

    public function __construct(UspsClient $client)
    {
        $this->client = $client;
    }

    public function track(string $trackingNumber): array
    {
        try {
            $response = $this->client->trackPackage($trackingNumber);

            return [
                'tracking_number' => $trackingNumber,
                'status' => $this->parseStatus($response),
                'events' => $this->parseEvents($response['trackingEvents'] ?? []),
                'estimated_delivery' => $this->parseEstimatedDelivery($response),
                'current_location' => $this->parseCurrentLocation($response)
            ];
        } catch (\Exception $e) {
            return [
                'tracking_number' => $trackingNumber,
                'error' => $e->getMessage(),
                'status' => 'unknown'
            ];
        }
    }

    public function updateShipmentTracking(string $trackingNumber): bool
    {
        $shipment = UspsShipment::where('tracking_number', $trackingNumber)->first();

        if (!$shipment) {
            return false;
        }

        $trackingInfo = $this->track($trackingNumber);

        if (isset($trackingInfo['error'])) {
            return false;
        }

        $shipment->update([
            'status' => $trackingInfo['status'],
            'tracking_events' => $trackingInfo['events'],
            'delivered_at' => $trackingInfo['status'] === 'delivered' ? now() : null
        ]);

        return true;
    }

    public function updateMultipleShipments(array $trackingNumbers = null): int
    {
        $query = UspsShipment::whereNotIn('status', ['delivered', 'returned']);

        if ($trackingNumbers) {
            $query->whereIn('tracking_number', $trackingNumbers);
        }

        $shipments = $query->get();
        $updated = 0;

        foreach ($shipments as $shipment) {
            if ($this->updateShipmentTracking($shipment->tracking_number)) {
                $updated++;
            }

            // Rate limiting - sleep between requests
            usleep(250000); // 250ms delay
        }

        return $updated;
    }

    protected function parseStatus(array $response): string
    {
        if (!isset($response['trackingEvents']) || empty($response['trackingEvents'])) {
            return 'unknown';
        }

        $latestEvent = $response['trackingEvents'][0];
        $eventType = strtolower($latestEvent['eventType'] ?? 'unknown');

        $statusMap = [
            'delivered' => 'delivered',
            'out for delivery' => 'out_for_delivery',
            'in transit' => 'in_transit',
            'departed' => 'in_transit',
            'arrived' => 'in_transit',
            'acceptance' => 'accepted',
            'pre-shipment' => 'pre_shipment',
            'return to sender' => 'returned'
        ];

        return $statusMap[$eventType] ?? 'in_transit';
    }

    protected function parseEvents(array $events): array
    {
        return array_map(function ($event) {
            return [
                'datetime' => isset($event['eventDateTime'])
                    ? Carbon::parse($event['eventDateTime'])->toISOString()
                    : null,
                'status' => $event['eventType'] ?? 'Unknown',
                'description' => $event['eventDescription'] ?? '',
                'location' => [
                    'city' => $event['eventCity'] ?? null,
                    'state' => $event['eventState'] ?? null,
                    'zip' => $event['eventZIP'] ?? null
                ]
            ];
        }, $events);
    }

    protected function parseEstimatedDelivery(array $response): ?string
    {
        if (isset($response['estimatedDeliveryDate'])) {
            return $response['estimatedDeliveryDate'];
        }

        // Look for estimated delivery in events
        foreach ($response['trackingEvents'] ?? [] as $event) {
            if (isset($event['estimatedDeliveryDate'])) {
                return $event['estimatedDeliveryDate'];
            }
        }

        return null;
    }

    protected function parseCurrentLocation(array $response): ?array
    {
        if (empty($response['trackingEvents'])) {
            return null;
        }

        $latestEvent = $response['trackingEvents'][0];

        return [
            'city' => $latestEvent['eventCity'] ?? null,
            'state' => $latestEvent['eventState'] ?? null,
            'zip' => $latestEvent['eventZIP'] ?? null
        ];
    }
}
