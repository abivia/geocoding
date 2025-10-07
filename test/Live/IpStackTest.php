<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use Abivia\Geocode\Geocoder;
use Abivia\Geocode\LookupService\IpStackApi;
use PHPUnit\Framework\TestCase;


class IpStackTest extends TestCase
{
    public function testLookup()
    {
        $keyfile = __DIR__ . '/../../.ipstack-key';
        if (!file_exists($keyfile)) {
            $this->fail("$keyfile file not found.");
        }
        $geocoder = new Geocoder(IpStackApi::make(trim(file_get_contents($keyfile))));
        $result = $geocoder->lookup('173.239.198.14');
        $this->assertNotNull($result);
        $this->assertNotNull($result);
        $expected = json_decode(
            file_get_contents(__DIR__ . '/Baseline-173.239.198.14.json'),
            true
        );
        foreach ($expected['exact'] as $method => $value) {
            if ($method === 'getTimezone') {
                continue;
            }
            $this->assertEquals($value, $result->$method(), $method);
        }
        foreach ($expected['close'] as $method => $value) {
            $this->assertEqualsWithDelta($value, $result->$method(), 0.1, $method);
        }
        foreach ($expected['includes'] as $method => $value) {
            if ($method === 'getLocale') {
                continue;
            }
            $this->assertStringContainsString($value, $result->$method(), $method);
        }
        // This provider gets weird postal codes.
        //foreach ($expected['starts'] as $method => $value) {
        //    $this->assertStringStartsWith($value, $result->$method(), $method);
        //}
    }

    public function testLookupLocalhost()
    {
        $keyFile = __DIR__ . '/../../.ipstack-key';
        if (file_exists($keyFile)) {
            $key = trim(file_get_contents($keyFile));
        } else {
            $key = '';
        }
        $geocoder = new Geocoder(IpStackApi::make($key));

        $result = $geocoder->lookup('127.0.0.1');
        $this->assertNotNull($result);
        $this->assertEquals(null, $result->getCountryCode());
    }

}
