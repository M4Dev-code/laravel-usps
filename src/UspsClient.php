<?php

namespace UspsShipping\Laravel;

class UspsClient
{
    protected string $clientId;
    protected string $clientSecret;
    protected string $baseUrl;
    protected bool $sandbox;
    protected array $config;
    protected ?string $accessToken = null;
    protected ?int $tokenExpiresAt = null;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->clientId = $config['client_id'];
        $this->clientSecret = $config['client_secret'];
        $this->sandbox = $config['sandbox'] ?? true;
        $this->baseUrl = $this->sandbox
            ? 'https://apis-tem.usps.com'
            : 'https://apis.usps.com';
    }

    /**
     * Get shipping rates for a package
     */
    public function getRates(array $params): array
    {
        $endpoint = '/prices/v3/base-rates/search';

        $payload = [
            'originZIPCode' => $params['origin_zip'],
            'destinationZIPCode' => $params['destination_zip'],
            'weight' => $params['weight'],
            'length' => $params['length'] ?? 0,
            'width' => $params['width'] ?? 0,
            'height' => $params['height'] ?? 0,
            'mailClass' => $params['mail_class'] ?? 'USPS_GROUND_ADVANTAGE',
            'processingCategory' => $params['processing_category'] ?? 'MACHINABLE',
            'rateIndicator' => $params['rate_indicator'] ?? 'SP',
            'destinationEntryFacilityType' => 'NONE',
            'priceType' => 'RETAIL'
        ];

        return $this->makeRequest('POST', $endpoint, $payload);
    }

    /**
     * Create shipping label
     */
    public function createLabel(array $params): array
    {
        $endpoint = '/labels/v3/label';

        $payload = [
            'labelAddress' => [
                'streetAddress' => $params['to_address']['street'],
                'streetAddressAbbreviation' => $params['to_address']['street2'] ?? '',
                'city' => $params['to_address']['city'],
                'state' => $params['to_address']['state'],
                'ZIPCode' => $params['to_address']['zip'],
                'ZIPPlus4' => $params['to_address']['zip4'] ?? ''
            ],
            'senderAddress' => [
                'streetAddress' => $params['from_address']['street'],
                'streetAddressAbbreviation' => $params['from_address']['street2'] ?? '',
                'city' => $params['from_address']['city'],
                'state' => $params['from_address']['state'],
                'ZIPCode' => $params['from_address']['zip'],
                'ZIPPlus4' => $params['from_address']['zip4'] ?? ''
            ],
            'packageDescription' => [
                'weight' => $params['weight'],
                'length' => $params['dimensions']['length'] ?? 0,
                'height' => $params['dimensions']['height'] ?? 0,
                'width' => $params['dimensions']['width'] ?? 0,
                'girth' => $params['dimensions']['girth'] ?? 0,
                'mailClass' => $params['service_type'] ?? 'USPS_GROUND_ADVANTAGE',
                'processingCategory' => 'MACHINABLE',
                'rateIndicator' => 'SP',
                'destinationEntryFacilityType' => 'NONE'
            ],
            'customerDetails' => [
                'customerName' => $params['customer_name'] ?? '',
                'customerEmail' => $params['customer_email'] ?? '',
                'customerPhoneNumber' => $params['customer_phone'] ?? ''
            ],
            'imageInfo' => [
                'imageType' => $params['label_format'] ?? 'PDF',
                'labelType' => $params['label_type'] ?? 'SHIPPING_LABEL_ONLY',
                'receiptOption' => 'SEPARATE_PAGE'
            ]
        ];

        return $this->makeRequest('POST', $endpoint, $payload);
    }

    /**
     * Validate and standardize address
     */
    public function validateAddress(array $address): array
    {
        $endpoint = '/addresses/v3/address';

        $payload = [
            'streetAddress' => $address['street'],
            'streetAddressAbbreviation' => $address['street2'] ?? '',
            'city' => $address['city'],
            'state' => $address['state'],
            'ZIPCode' => $address['zip'],
            'ZIPPlus4' => $address['zip4'] ?? ''
        ];

        return $this->makeRequest('POST', $endpoint, $payload);
    }

    /**
     * Track package
     */
    public function trackPackage(string $trackingNumber): array
    {
        $endpoint = "/tracking/v3/tracking/{$trackingNumber}";

        return $this->makeRequest('GET', $endpoint);
    }

    /**
     * Get service availability
     */
    public function getServiceAvailability(array $params): array
    {
        $endpoint = '/service-standards/v3/estimates';

        $payload = [
            'originZIPCode' => $params['origin_zip'],
            'destinationZIPCode' => $params['destination_zip'],
            'mailClass' => $params['service_type'] ?? 'USPS_GROUND_ADVANTAGE',
            'acceptanceDate' => $params['ship_date'] ?? date('Y-m-d')
        ];

        return $this->makeRequest('POST', $endpoint, $payload);
    }

    /**
     * Get ZIP code information
     */
    public function getZipInfo(string $zipCode): array
    {
        $endpoint = "/addresses/v3/city-state";

        $payload = ['ZIPCode' => $zipCode];

        return $this->makeRequest('POST', $endpoint, $payload);
    }

    /**
     * Make HTTP request to USPS API
     */
    protected function makeRequest(string $method, string $endpoint, array $data = []): array
    {
        $url = $this->baseUrl . $endpoint;

        $headers = [
            'Authorization' => 'Bearer ' . $this->getAccessToken(),
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => array_map(
                fn($k, $v) => "$k: $v",
                array_keys($headers),
                $headers
            ),
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CUSTOMREQUEST => $method,
        ]);

        if (!empty($data) && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new Exceptions\UspsApiException("cURL Error: $error");
        }

        $decodedResponse = json_decode($response, true);

        if ($httpCode >= 400) {
            $errorMessage = $decodedResponse['error']['message'] ?? 'Unknown API error';
            throw new Exceptions\UspsApiException("USPS API Error (HTTP $httpCode): $errorMessage", $httpCode);
        }

        return $decodedResponse ?? [];
    }

    /**
     * Get OAuth access token with proper caching
     */
    protected function getAccessToken(): string
    {
        // Return cached token if still valid
        if ($this->accessToken && $this->tokenExpiresAt && time() < $this->tokenExpiresAt - 300) {
            return $this->accessToken;
        }

        // Get new token
        $tokenEndpoint = $this->baseUrl . '/oauth2/v3/token';

        $payload = [
            'grant_type' => 'client_credentials',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'scope' => 'addresses prices labels tracking service-standards'
        ];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $tokenEndpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($payload),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded',
                'Accept: application/json'
            ],
            CURLOPT_TIMEOUT => 30
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new Exceptions\UspsAuthenticationException("cURL Error during authentication: $error");
        }

        if ($httpCode !== 200) {
            throw new Exceptions\UspsAuthenticationException("Authentication failed with HTTP code: $httpCode. Response: $response");
        }

        $tokenData = json_decode($response, true);

        if (!isset($tokenData['access_token'])) {
            throw new Exceptions\UspsAuthenticationException('Failed to obtain access token. Response: ' . $response);
        }

        // Cache the token
        $this->accessToken = $tokenData['access_token'];
        $this->tokenExpiresAt = time() + ($tokenData['expires_in'] ?? 3600);

        return $this->accessToken;
    }
}
