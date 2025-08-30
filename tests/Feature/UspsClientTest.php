<?php

namespace UspsShipping\Laravel\Tests\Feature;

use Orchestra\Testbench\TestCase;
use UspsShipping\Laravel\UspsClient;
use UspsShipping\Laravel\UspsServiceProvider;

class UspsClientTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [UspsServiceProvider::class];
    }

    protected function defineEnvironment($app)
    {
        $app['config']->set('usps', [
            'client_id' => 'test_client',
            'client_secret' => 'test_secret',
            'sandbox' => true,
            'default_service' => 'USPS_GROUND_ADVANTAGE',
            'services' => [
                'USPS_GROUND_ADVANTAGE' => ['name' => 'USPS Ground Advantage']
            ],
            'cache' => ['enabled' => false]
        ]);
    }

    /** @test */
    public function it_can_validate_address()
    {
        $client = new UspsClient(config('usps'));

        $this->assertTrue(method_exists($client, 'validateAddress'));
    }

    /** @test */
    public function it_can_get_rates()
    {
        $client = new UspsClient(config('usps'));

        $this->assertTrue(method_exists($client, 'getRates'));
    }

    /** @test */
    public function it_can_create_labels()
    {
        $client = new UspsClient(config('usps'));

        $this->assertTrue(method_exists($client, 'createLabel'));
    }

    /** @test */
    public function it_can_track_packages()
    {
        $client = new UspsClient(config('usps'));

        $this->assertTrue(method_exists($client, 'trackPackage'));
    }

    /** @test */
    public function it_throws_exception_without_credentials()
    {
        $this->expectException(\Exception::class);

        $client = new UspsClient([]);
    }
}
