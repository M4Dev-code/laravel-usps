<?php

namespace M4dev\UspsShip\Auth;

use Illuminate\Support\Facades\Cache;
use GuzzleHttp\Client;

class OAuthTokenCache
{
    protected array $config;
    protected Client $http;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->http = new Client([
            'timeout' => $config['oauth']['timeout'] ?? 10,
        ]);
    }

    public function token(): string
    {
        return Cache::remember(
            $this->config['oauth']['cache_key'],
            now()->addMinutes(50),
            fn() => $this->requestNewToken()
        );
    }

    protected function requestNewToken(): string
    {
        $resp = $this->http->post($this->config['oauth']['token_url'], [
            'form_params' => [
                'grant_type' => 'client_credentials',
                'client_id' => $this->config['oauth']['client_id'],
                'client_secret' => $this->config['oauth']['client_secret'],
                'scope' => $this->config['oauth']['scopes'],
            ],
        ]);

        $body = json_decode((string) $resp->getBody(), true);

        if (!isset($body['access_token'])) {
            throw new \RuntimeException('USPS OAuth failed: no access_token');
        }

        return $body['access_token'];
    }

    public function forget(): void
    {
        Cache::forget($this->config['oauth']['cache_key']);
    }
}
