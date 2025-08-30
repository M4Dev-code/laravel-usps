<?php

namespace UspsShipping\Laravel\Services;

use UspsShipping\Laravel\UspsClient;
use UspsShipping\Laravel\Exceptions\UspsValidationException;

class AddressValidationService
{
    protected UspsClient $client;

    public function __construct(UspsClient $client)
    {
        $this->client = $client;
    }

    public function validate(array $address): array
    {
        try {
            $result = $this->client->validateAddress($address);

            if (!isset($result['address'])) {
                throw new UspsValidationException('Invalid address validation response');
            }

            return [
                'valid' => true,
                'original' => $address,
                'standardized' => $result['address'],
                'corrections' => $this->getCorrections($address, $result['address'])
            ];
        } catch (\Exception $e) {
            return [
                'valid' => false,
                'original' => $address,
                'error' => $e->getMessage()
            ];
        }
    }

    protected function getCorrections(array $original, array $standardized): array
    {
        $corrections = [];

        $fields = ['streetAddress', 'city', 'state', 'ZIPCode'];

        foreach ($fields as $field) {
            $originalKey = $this->mapFieldName($field);

            if (isset($original[$originalKey]) && isset($standardized[$field])) {
                if ($original[$originalKey] !== $standardized[$field]) {
                    $corrections[$field] = [
                        'original' => $original[$originalKey],
                        'corrected' => $standardized[$field]
                    ];
                }
            }
        }

        return $corrections;
    }

    protected function mapFieldName(string $apiField): string
    {
        $mapping = [
            'streetAddress' => 'street',
            'city' => 'city',
            'state' => 'state',
            'ZIPCode' => 'zip'
        ];

        return $mapping[$apiField] ?? $apiField;
    }
}
