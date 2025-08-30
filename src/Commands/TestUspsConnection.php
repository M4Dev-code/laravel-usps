<?php

namespace UspsShipping\Laravel\Commands;

use Illuminate\Console\Command;

class TestUspsConnection extends Command
{
    protected $signature = 'usps:test-connection';
    protected $description = 'Test USPS API connection and authentication';

    public function handle()
    {
        try {
            $usps = app('usps');

            $this->info('Testing USPS API connection...');

            // Test OAuth token generation
            $reflection = new \ReflectionClass($usps);
            $method = $reflection->getMethod('getAccessToken');
            $method->setAccessible(true);
            $token = $method->invoke($usps);

            $this->info('✓ OAuth authentication successful');
            $this->line('Token preview: ' . substr($token, 0, 20) . '...');

            // Test ZIP code lookup
            $result = $usps->getZipInfo('90210');
            $this->info('✓ ZIP code lookup test successful');
            $this->line('Test ZIP Code Info: ' . json_encode($result, JSON_PRETTY_PRINT));

            // Test address validation
            $addressResult = $usps->validateAddress([
                'street' => '123 Main St',
                'city' => 'Beverly Hills',
                'state' => 'CA',
                'zip' => '90210'
            ]);
            $this->info('✓ Address validation test successful');

            $this->info('All USPS API tests passed successfully!');
        } catch (\Exception $e) {
            $this->error('USPS API connection failed: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
