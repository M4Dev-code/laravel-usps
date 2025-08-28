<?php // src/Http/HttpClient.php

namespace m4dev\UspsShip\Http;

use GuzzleHttp\Client;
use m4dev\UspsShip\Auth\OAuthTokenCache;

class HttpClient
{
    protected array $config;
    protected Client $client;
    protected OAuthTokenCache $tokenCache;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->client = new Client([
            'timeout' => $config['oauth']['timeout'] ?? 10,
        ]);

        $this->tokenCache = new OAuthTokenCache($config);
    }

    protected function token(): string
    {
        return $this->tokenCache->token();
    }

    public function baseUrl(string $product): string
    {
        $env = $this->config['env'] === 'production' ? 'production' : 'sandbox';
        return rtrim($this->config['base_urls'][$env][$product] ?? '', '/');
    }

    public function get(string $product, string $path, array $query = [])
    {
        return $this->request('GET', $product, $path, ['query' => $query]);
    }

    public function post(string $product, string $path, array $json = [])
    {
        return $this->request('POST', $product, $path, ['json' => $json]);
    }

    public function delete(string $product, string $path)
    {
        return $this->request('DELETE', $product, $path);
    }

    protected function request(string $method, string $product, string $path, array $options = [])
    {
        $headers = [
            'Authorization' => 'Bearer ' . $this->token(),
            'Accept' => 'application/json',
        ];

        $resp = $this->client->request(
            $method,
            $this->baseUrl($product) . $path,
            $options + ['headers' => $headers]
        );

        return json_decode((string) $resp->getBody(), true);
    }
}
