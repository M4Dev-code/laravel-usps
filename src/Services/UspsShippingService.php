<?php

namespace UspsShipping\Laravel\Services;

use UspsShipping\Laravel\UspsClient;
use UspsShipping\Laravel\Models\UspsShipment;

class UspsShippingService
{
    protected UspsClient $client;
    protected AddressValidationService $addressService;
    protected RateService $rateService;
    protected LabelService $labelService;

    public function __construct(UspsClient $client)
    {
        $this->client = $client;
        $this->addressService = app(AddressValidationService::class);
        $this->rateService = app(RateService::class);
        $this->labelService = app(LabelService::class);
    }

    /**
     * Create a complete shipment with rate calculation and label generation
     */
    public function createShipment(array $shipmentData): UspsShipment
    {
        // Validate addresses first
        $fromValidation = $this->addressService->validate($shipmentData['from_address']);
        $toValidation = $this->addressService->validate($shipmentData['to_address']);

        if (!$fromValidation['valid']) {
            throw new \Exception('Invalid from address: ' . ($fromValidation['error'] ?? 'Unknown error'));
        }

        if (!$toValidation['valid']) {
            throw new \Exception('Invalid to address: ' . ($toValidation['error'] ?? 'Unknown error'));
        }

        // Use standardized addresses
        $fromAddress = $fromValidation['standardized'];
        $toAddress = $toValidation['standardized'];

        // Get rates if not provided
        if (!isset($shipmentData['selected_service'])) {
            $rates = $this->rateService->getRates([
                'origin_zip' => $fromAddress['ZIPCode'],
                'destination_zip' => $toAddress['ZIPCode'],
                'weight' => $shipmentData['weight'],
                'length' => $shipmentData['dimensions']['length'] ?? 0,
                'width' => $shipmentData['dimensions']['width'] ?? 0,
                'height' => $shipmentData['dimensions']['height'] ?? 0,
                'mail_class' => $shipmentData['service_type'] ?? 'USPS_GROUND_ADVANTAGE'
            ]);
        }

        // Create label with standardized addresses
        $labelData = array_merge($shipmentData, [
            'from_address' => [
                'street' => $fromAddress['streetAddress'],
                'street2' => $fromAddress['streetAddressAbbreviation'] ?? '',
                'city' => $fromAddress['city'],
                'state' => $fromAddress['state'],
                'zip' => $fromAddress['ZIPCode'],
                'zip4' => $fromAddress['ZIPPlus4'] ?? ''
            ],
            'to_address' => [
                'street' => $toAddress['streetAddress'],
                'street2' => $toAddress['streetAddressAbbreviation'] ?? '',
                'city' => $toAddress['city'],
                'state' => $toAddress['state'],
                'zip' => $toAddress['ZIPCode'],
                'zip4' => $toAddress['ZIPPlus4'] ?? ''
            ]
        ]);

        return $this->labelService->createAndSaveLabel($labelData);
    }

    /**
     * Update shipment tracking status
     */
    public function updateTrackingStatus(string $trackingNumber): bool
    {
        $shipment = UspsShipment::where('tracking_number', $trackingNumber)->first();

        if (!$shipment) {
            return false;
        }

        $trackingInfo = $this->client->trackPackage($trackingNumber);

        if (isset($trackingInfo['trackingEvents'])) {
            $latestEvent = $trackingInfo['trackingEvents'][0];
            $shipment->status = strtolower($latestEvent['eventType']);

            if ($latestEvent['eventType'] === 'DELIVERED') {
                $shipment->delivered_at = $latestEvent['eventDateTime'];
            }

            $shipment->tracking_events = $trackingInfo['trackingEvents'];
            $shipment->save();
        }

        return true;
    }

    /**
     * Get estimated delivery time
     */
    public function getDeliveryEstimate(array $params): array
    {
        return $this->client->getServiceAvailability($params);
    }

    /**
     * Batch create multiple shipments
     */
    public function createBatchShipments(array $shipments): array
    {
        $results = [];

        foreach ($shipments as $index => $shipmentData) {
            try {
                $results[$index] = [
                    'success' => true,
                    'shipment' => $this->createShipment($shipmentData)
                ];
            } catch (\Exception $e) {
                $results[$index] = [
                    'success' => false,
                    'error' => $e->getMessage(),
                    'data' => $shipmentData
                ];
            }
        }

        return $results;
    }
}
