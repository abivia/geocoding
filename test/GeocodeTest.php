<?php


use Abivia\Geocode\Geocoder;
use PHPUnit\Framework\TestCase;

require_once 'FakeService.php';

class GeocodeTest extends TestCase
{
    public Geocoder $testObj;
    public function setUp(): void
    {
        parent::setUp();
        $this->testObj = new Geocoder(new FakeService());
    }

    public function testLookup()
    {
        $result = $this->testObj->lookup('173.239.198.14');
        $this->assertNotNull($result);
        $this->assertEquals('US', $result->countryCode());
        $this->assertEquals('173.239.198.14', $result->ipAddress());
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
        $_SERVER['REMOTE_ADDR'] = '67.61.113.220';
        $result = $this->testObj->lookupHttp();
        $this->assertNotNull($result);
        $this->assertEquals('US', $result->countryCode());
        $this->assertEquals('67.61.113.220', $result->ipAddress());
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '148.170.126.209';
        $result = $this->testObj->lookupHttp();
        $this->assertNotNull($result);
        $this->assertEquals('CA', $result->countryCode());
        $this->assertEquals('148.170.126.209', $result->ipAddress());
    }

    public function testLookupHttpNoServer()
    {
        $this->expectException(\Abivia\Geocode\AddressNotFoundException::class);
        unset($_SERVER['REMOTE_ADDR']);
        unset($_SERVER['HTTP_X_FORWARDED_FOR']);
        $this->testObj->lookupHttp();
    }

    public function testLookupSubNetV4()
    {
        $result = $this->testObj->lookup('173.239.198.14');
        $this->assertNotNull($result);
        $this->assertEquals('173.239.198.14', $result->ipAddress());
    }

    public function testLookupSubNetV4Cached()
    {
        $result = $this->testObj->lookup('173.239.198.14');
        $this->assertNotNull($result);
        $result = $this->testObj->lookupSubNet('173.239.198.99');
        $this->assertNotNull($result);
        $this->assertEquals('173.239.198.14', $result->ipAddress());
    }

}
