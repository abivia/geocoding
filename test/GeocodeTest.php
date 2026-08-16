<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Abivia\Geocode\AddressNotFoundException;
use Abivia\Geocode\Geocoder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

require_once 'LookupService/FakeCacheableService.php';

class GeocodeTest extends TestCase
{
    public FakeCacheableService $lookupService;
    public Geocoder $testObj;

    private function record(string $ip, $country = 'CA'): array
    {
        return [
            'ip' => $ip,
            'city' => 'Testerville',
            'country_name' => 'Canada',
            'country_code' => $country,
            'is_crawler' => false,
            'is_tor' => false,
        ];
    }

    public function setUp(): void
    {
        parent::setUp();
        $this->lookupService = new FakeCacheableService();
        $this->testObj = new Geocoder($this->lookupService);
    }

    public function testCopyNormalized()
    {
        $this->lookupService->data['173.239.198.14'] = $this->record('173.239.198.14');
        $lookup = $this->testObj->lookup('173.239.198.14');
        $result = $lookup->copyNormalized(['asn' => 'AS1234']);
        $this->assertEquals('AS1234', $result->getAsn());
        $source = $result->getData();
        $this->assertFalse(isset($source['connection']['asn']));
    }

    public function testCopyWith()
    {
        $this->lookupService->data['173.239.198.14'] = $this->record('173.239.198.14');
        $lookup = $this->testObj->lookup('173.239.198.14');
        $result = $lookup->copyWith(['connection' => ['asn' => 'AS1234']]);
        $this->assertEquals('AS1234', $result->getAsn());
    }

    public function testLookup()
    {
        $this->lookupService->data['173.239.198.14'] = $this->record('173.239.198.14');
        $result = $this->testObj->lookup('173.239.198.14');
        $this->assertNotNull($result);
        $this->assertEquals('CA', $result->getCountryCode());
        $this->assertEquals('173.239.198.14', $result->getIpAddress());
    }

    public function testLookupBad()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->testObj->lookup('173.888.198.14');
    }

    public function testLookupNotFound()
    {
        $result = $this->testObj->lookup('110.120.130.140');
        $this->assertNull($result);
    }

    public function testLookupHttp()
    {
        $this->lookupService->data['67.61.113.220'] = $this->record('67.61.113.220', 'US');
        $this->lookupService->data['148.170.126.209'] = $this->record('148.170.126.209');
        $_SERVER = ['REMOTE_ADDR' => '67.61.113.220'];
        $result = $this->testObj->lookupHttp();
        $this->assertNotNull($result);
        $this->assertEquals('US', $result->getCountryCode());
        $this->assertEquals('67.61.113.220', $result->getIpAddress());
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '148.170.126.209';
        $result = $this->testObj->lookupHttp();
        $this->assertNotNull($result);
        $this->assertEquals('CA', $result->getCountryCode());
        $this->assertEquals('148.170.126.209', $result->getIpAddress());
        $result = $this->testObj->lookupHttp(false);
        $this->assertNotNull($result);
        $this->assertEquals('US', $result->getCountryCode());
        $this->assertEquals('67.61.113.220', $result->getIpAddress());
    }

    public function testLookupHttpViaProxy()
    {
        $this->lookupService->data['67.61.113.220'] = $this->record('67.61.113.220', 'US');
        $this->lookupService->data['148.170.126.209'] = $this->record('148.170.126.209');
        $_SERVER = ['REMOTE_ADDR' => '67.61.113.220'];
        $result = $this->testObj->lookupHttp();
        $this->assertNotNull($result);
        $this->assertEquals('US', $result->getCountryCode());
        $this->assertEquals('67.61.113.220', $result->getIpAddress());

        // Add a nonstandard proxy header, expect it to not get picked up.
        $_SERVER['HTTP_CLIENT_IP'] = '148.170.126.209';
        $result = $this->testObj->lookupHttp();
        $this->assertNotNull($result);
        $this->assertEquals('US', $result->getCountryCode());
        $this->assertEquals('67.61.113.220', $result->getIpAddress());

        // Now add the known proxy headers and expect the proxy to be picked up.
        $this->testObj->addKnownProxyHeaders();
        $result = $this->testObj->lookupHttp();
        $this->assertNotNull($result);
        $this->assertEquals('CA', $result->getCountryCode());
        $this->assertEquals('148.170.126.209', $result->getIpAddress());

        // Lastly, delete the header we used and expect the proxy to be ignored.
        $this->testObj->removeProxyHeader('HTTP_CLIENT_IP');
        $result = $this->testObj->lookupHttp();
        $this->assertNotNull($result);
        $this->assertEquals('US', $result->getCountryCode());
        $this->assertEquals('67.61.113.220', $result->getIpAddress());
    }

    public function testLookupHttpBadForward()
    {
        $this->lookupService->data['67.61.113.220'] = $this->record('67.61.113.220', 'US');
        // Check the bad actor case
        $_SERVER = [
            'REMOTE_ADDR' => '67.61.113.220',
            'HTTP_X_FORWARDED_FOR' => 'I am an asshole'
        ];
        $result = $this->testObj->lookupHttp();
        $this->assertNotNull($result);
        $this->assertEquals('US', $result->getCountryCode());
        $this->assertEquals('67.61.113.220', $result->getIpAddress());
    }

    public function testLookupHttpNoForward()
    {
        $this->lookupService->data['67.61.113.220'] = $this->record('67.61.113.220', 'US');
        $this->lookupService->data['148.170.126.209'] = $this->record('148.170.126.209');

        $_SERVER = [
            'REMOTE_ADDR' => '67.61.113.220',
            'HTTP_X_FORWARDED_FOR' => '148.170.126.209'
        ];
        $result = $this->testObj->lookupHttp(false);
        $this->assertNotNull($result);
        $this->assertEquals('US', $result->getCountryCode());
        $this->assertEquals('67.61.113.220', $result->getIpAddress());
    }

    public function testLookupHttpNoServer()
    {
        $_SERVER = [];
        $this->expectException(AddressNotFoundException::class);
        $this->testObj->lookupHttp();
    }

    public function testLookupSubNetV4()
    {
        $this->lookupService->data['173.239.198.14'] = $this->record('173.239.198.14');
        $result = $this->testObj->lookup('173.239.198.14');
        $this->assertNotNull($result);
        $this->assertEquals('173.239.198.14', $result->getIpAddress());
    }

    public function testLookupSubNetV4Cached()
    {
        $this->lookupService->data['173.239.198.14'] = $this->record('173.239.198.14');
        $this->lookupService->setCache(new ArrayAdapter());
        $result = $this->testObj->lookup('173.239.198.14');
        $this->assertNotNull($result);
        $result = $this->testObj->lookupSubNet('173.239.198.99');
        $this->assertNotNull($result);
        $this->assertEquals('173.239.198.14', $result->getIpAddress());
    }

}
