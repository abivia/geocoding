<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use Abivia\Geocode\LookupFailedException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

require_once 'IpInfoApiFallbackDouble.php';

class IpInfoApiTest extends TestCase
{
    private function body(string $tier, string $ip): string
    {
        return match ($tier) {
            'lite' => json_encode(['ip' => $ip, 'city' => 'Testerville', 'country' => 'CA']),
            'free' => json_encode([
                'ip' => $ip,
                'city' => 'Testerville',
                'country' => 'CA',
                'org' => 'AS64500 Example Org',
            ]),
            'core' => json_encode([
                'ip' => $ip,
                'geo' => [
                    'latitude' => '1.0',
                    'longitude' => '2.0',
                    'city' => 'Testerville',
                    'country' => 'CA',
                ],
                'as' => [
                    'asn' => 'AS64500',
                    'name' => 'Example Org',
                    'domain' => 'example.com',
                    'type' => 'hosting',
                ],
                'is_anonymous' => false,
                'is_anycast' => false,
                'is_hosting' => false,
                'is_mobile' => false,
                'is_satellite' => false,
            ]),
        };
    }

    public function testFirstTierSuccessDoesNotFallBack()
    {
        $service = IpInfoApiFallbackDouble::make(['key' => '', 'apiList' => ['lite', 'free']]);
        $service->responses = ['lite' => [200, $this->body('lite', '1.2.3.4')]];

        $result = $service->query('1.2.3.4');

        $this->assertNotNull($result);
        $this->assertSame('1.2.3.4', $result->getIpAddress());
        $this->assertSame(['lite'], $service->calledTiers);
    }

    public function testFallsBackToNextTierOnRateLimit()
    {
        $service = IpInfoApiFallbackDouble::make(['key' => '', 'apiList' => ['lite', 'free']]);
        $service->responses = [
            'lite' => [429, ''],
            'free' => [200, $this->body('free', '1.2.3.4')],
        ];

        $result = $service->query('1.2.3.4');

        $this->assertNotNull($result);
        $this->assertSame('1.2.3.4', $result->getIpAddress());
        $this->assertSame(['lite', 'free'], $service->calledTiers);
    }

    public function testFallsBackThroughAllThreeTiers()
    {
        $service = IpInfoApiFallbackDouble::make(['key' => '', 'apiList' => ['lite', 'free', 'core']]);
        $service->responses = [
            'lite' => [429, ''],
            'free' => [429, ''],
            'core' => [200, $this->body('core', '1.2.3.4')],
        ];

        $result = $service->query('1.2.3.4');

        $this->assertNotNull($result);
        $this->assertSame(['lite', 'free', 'core'], $service->calledTiers);
    }

    public function testThrowsWhenEveryTierIsRateLimited()
    {
        $service = IpInfoApiFallbackDouble::make(['key' => '', 'apiList' => ['lite', 'free']]);
        $service->responses = [
            'lite' => [429, ''],
            'free' => [429, ''],
        ];

        $this->expectException(LookupFailedException::class);
        $service->query('1.2.3.4');
    }

    public function testExhaustedTierIsSkippedOnSubsequentLookups()
    {
        $service = IpInfoApiFallbackDouble::make(['key' => '', 'apiList' => ['lite', 'free']]);
        $service->setCache(new ArrayAdapter());
        $service->responses = [
            'lite' => [429, ''],
            'free' => [200, $this->body('free', '1.2.3.4')],
        ];
        $service->query('1.2.3.4');
        $this->assertSame(['lite', 'free'], $service->calledTiers);

        // A fresh address should skip 'lite': it was just cached as exhausted
        // for the rest of the month.
        $service->calledTiers = [];
        $service->responses = ['free' => [200, $this->body('free', '5.6.7.8')]];

        $result = $service->query('5.6.7.8');

        $this->assertNotNull($result);
        $this->assertSame(['free'], $service->calledTiers);
    }

    public function testHttpErrorIsReportedAsLookupFailure()
    {
        $service = IpInfoApiFallbackDouble::make(['key' => '', 'apiList' => ['lite']]);
        $service->responses = ['lite' => [500, '']];

        $this->expectException(LookupFailedException::class);
        $service->query('1.2.3.4');
    }
}
