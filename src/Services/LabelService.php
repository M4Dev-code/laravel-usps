<?php

namespace UspsShipping\Laravel\Services;

use UspsShipping\Laravel\UspsClient;
use UspsShipping\Laravel\Models\UspsShipment;
use UspsShipping\Laravel\Exceptions\UspsException;

class LabelService
{
    protected UspsClient $client;
    protected array $config;

    public function __construct(UspsClient $client, array $config)
    {
        $this->client = $client;
        $this->config = $config;
    }

    public function createLabel(array $params): array
    {
        $labelData = $this->prepareLabelData($params);

        try {
            $response = $this->client->createLabel($labelData);

            if (!isset($response['labelMetadata']['trackingNumber'])) {
                throw new UspsException('No tracking number received from USPS');
            }

            return [
                'tracking_number' => $response['labelMetadata']['trackingNumber'],
                'label_url' => $response['labelImage']['labelImageURL'] ?? null,
                'label_base64' => $response['labelImage']['labelImageBase64'] ?? null,
                'cost' => $response['labelMetadata']['postage'] ?? null,
                'service_type' => $labelData['packageDescription']['mailClass'],
                'label_format' => $params['label_format'] ?? $this->config['label_format'],
            ];
        } catch (\Exception $e) {
            throw new UspsException('Failed to create shipping label: ' . $e->getMessage());
        }
    }

    public function createAndSaveLabel(array $params): UspsShipment
    {
        $labelInfo = $this->createLabel($params);

        return UspsShipment::create([
            'tracking_number' => $labelInfo['tracking_number'],
            'service_type' => $labelInfo['service_type'],
            'from_address' => $params['from_address'],
            'to_address' => $params['to_address'],
            'weight' => $params['weight'],
            'dimensions' => $params['dimensions'] ?? null,
            'cost' => $labelInfo['cost'],
            'label_url' => $labelInfo['label_url'],
            'label_base64' => $labelInfo['label_base64'],
            'status' => 'label_created',
            'shipped_at' => now(),
            'metadata' => $params['metadata'] ?? null
        ]);
    }

    protected function prepareLabelData(array $params): array
    {
        // Use default sender if not provided
        $fromAddress = array_merge($this->config['default_sender'], $params['from_address'] ?? []);

        return [
            'labelAddress' => [
                'streetAddress' => $params['to_address']['street'],
                'streetAddressAbbreviation' => $params['to_address']['street2'] ?? '',
                'city' => $params['to_address']['city'],
                'state' => $params['to_address']['state'],
                'ZIPCode' => $params['to_address']['zip'],
                'ZIPPlus4' => $params['to_address']['zip4'] ?? ''
            ],
            'senderAddress' => [
                'streetAddress' => $fromAddress['street'],
                'streetAddressAbbreviation' => $fromAddress['street2'] ?? '',
                'city' => $fromAddress['city'],
                'state' => $fromAddress['state'],
                'ZIPCode' => $fromAddress['zip'],
                'ZIPPlus4' => $fromAddress['zip4'] ?? ''
            ],
            'packageDescription' => [
                'weight' => $params['weight'],
                'length' => $params['dimensions']['length'] ?? 0,
                'height' => $params['dimensions']['height'] ?? 0,
                'width' => $params['dimensions']['width'] ?? 0,
                'girth' => $params['dimensions']['girth'] ?? 0,
                'mailClass' => $params['service_type'] ?? $this->config['default_service'],
                'processingCategory' => $params['processing_category'] ?? 'MACHINABLE',
                'rateIndicator' => $params['rate_indicator'] ?? 'SP',
                'destinationEntryFacilityType' => 'NONE'
            ],
            'customerDetails' => [
                'customerName' => $params['customer_name'] ?? $fromAddress['name'],
                'customerEmail' => $params['customer_email'] ?? $fromAddress['email'],
                'customerPhoneNumber' => $params['customer_phone'] ?? $fromAddress['phone']
            ],
            'imageInfo' => [
                'imageType' => $params['label_format'] ?? $this->config['label_format'],
                'labelType' => $params['label_type'] ?? $this->config['label_type'],
                'receiptOption' => $params['receipt_option'] ?? 'SEPARATE_PAGE'
            ]
        ];
    }
}
