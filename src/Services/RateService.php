<?php

namespace UspsShipping\Laravel\Services;

use UspsShipping\Laravel\UspsClient;
use UspsShipping\Laravel\Models\UspsRateCache;

class RateService
{
    protected UspsClient $client;
    protected array $config;

    public function __construct(UspsClient $client, array $config)
    {
        $this->client = $client;
        $this->config = $config;
    }

    public function getRates(array $params, bool $useCache = true): array
    {
        if ($useCache && $this->config['cache']['enabled']) {
            $cacheKey = $this->generateCacheKey($params);
            $cachedRates = UspsRateCache::getCachedRates($cacheKey);

            if ($cachedRates) {
                return $cachedRates;
            }
        }

        $rates = $this->fetchRates($params);

        if ($useCache && $this->config['cache']['enabled']) {
            UspsRateCache::cacheRates(
                $this->generateCacheKey($params),
                $params,
                $rates,
                $this->config['cache']['duration']
            );
        }

        return $rates;
    }

    protected function fetchRates(array $params): array
    {
        if ($this->config['rate_shopping']['enabled']) {
            return $this->getRatesShopping($params);
        }

        return $this->client->getRates($params);
    }

    protected function getRatesShopping(array $params): array
    {
        $services = $this->config['rate_shopping']['services'];
        $rates = [];

        foreach ($services as $service) {
            try {
                $serviceParams = array_merge($params, ['mail_class' => $service]);
                $serviceRates = $this->client->getRates($serviceParams);

                if (isset($serviceRates['totalBasePrice'])) {
                    $rates[] = array_merge($serviceRates, [
                        'service_type' => $service,
                        'service_name' => $this->config['services'][$service]['name'] ?? $service
                    ]);
                }
            } catch (\Exception $e) {
                // Log error but continue with other services
                \Log::warning("Failed to get rates for service {$service}: " . $e->getMessage());
            }
        }

        // Sort rates
        $sortBy = $this->config['rate_shopping']['sort_by'];
        if ($sortBy === 'price') {
            usort($rates, fn($a, $b) => $a['totalBasePrice'] <=> $b['totalBasePrice']);
        } elseif ($sortBy === 'delivery_time') {
            // Sort by estimated delivery days
            usort(
                $rates,
                fn($a, $b) =>
                $this->getEstimatedDays($a['service_type']) <=>
                    $this->getEstimatedDays($b['service_type'])
            );
        }

        return ['rates' => $rates];
    }

    protected function getEstimatedDays(string $serviceType): int
    {
        $deliveryDays = $this->config['services'][$serviceType]['delivery_days'] ?? '5';
        return (int) explode('-', $deliveryDays)[0];
    }

    protected function generateCacheKey(array $params): string
    {
        return $this->config['cache']['prefix'] . md5(serialize($params));
    }
}
