<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use Abivia\Geocode\Geocoder;
use Abivia\Geocode\LookupFailedException;
use Abivia\Geocode\LookupService\IpApiApi;
use PHPUnit\Framework\TestCase;


class IpApiTest extends TestCase
{
    public function testLookup()
    {
        $keyFile = __DIR__ . '/../../.ipapi-key';
        if (file_exists($keyFile)) {
            $key = trim(file_get_contents($keyFile));
        } else {
            $key = '';
        }
        $geocoder = new Geocoder(IpApiApi::make($key));
        $result = $geocoder->lookup('173.239.198.14');
        $this->assertNotNull($result);
        $expected = json_decode(
            file_get_contents(__DIR__ . '/Baseline-173.239.198.14.json'),
            true
        );
        foreach ($expected['exact'] as $method => $value) {
            $this->assertEquals($value, $result->$method(), $method);
        }
        foreach ($expected['close'] as $method => $value) {
            $this->assertEqualsWithDelta($value, $result->$method(), 0.1, $method);
        }
        foreach ($expected['includes'] as $method => $value) {
            $this->assertStringContainsString($value, $result->$method(), $method);
        }
        foreach ($expected['starts'] as $method => $value) {
            $this->assertStringStartsWith($value, $result->$method(), $method);
        }

    }

    public function testLookupLocalhost()
    {
        $keyFile = __DIR__ . '/../../.ipapi-key';
        if (file_exists($keyFile)) {
            $key = trim(file_get_contents($keyFile));
        } else {
            $key = '';
        }
        $geocoder = new Geocoder(IpApiApi::make($key));
        $this->expectException(LookupFailedException::class);
        $result = $geocoder->lookup('127.0.0.1');
        $this->assertNotNull($result);
    }

}
