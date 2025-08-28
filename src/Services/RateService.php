<?php // src/Services/RateService.php

namespace m4dev\UspsShip\Services;

use GuzzleHttp\Client;
use m4dev\UspsShip\Support\Xml;

class RateService
{
    protected array $config;
    protected Client $http;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->http = new Client(['timeout' => $config['webtools']['timeout'] ?? 10]);
    }

    /**
     * Get domestic rates (RateV4 API, XML)
     *
     * @param array $package [weight_oz, zip_orig, zip_dest, size, container, machinable]
     */
    public function domestic(array $package): array
    {
        $xml = Xml::buildRateV4([
            'user_id' => $this->config['webtools']['user_id'],
            'package' => $package,
        ]);

        $query = ['API' => 'RateV4', 'XML' => $xml];
        $resp = $this->http->get($this->config['webtools']['rate_url'], ['query' => $query]);
        $body = (string) $resp->getBody();
        $dom = simplexml_load_string($body);

        $out = [];
        foreach ($dom->Package->Postage ?? [] as $p) {
            $out[] = [
                'mail_service' => (string) $p->MailService,
                'rate' => (float) $p->Rate,
                'class_id' => (string) $p['CLASSID'],
            ];
        }
        return $out;
    }
}
