<?php

namespace Abivia\Geocode\CacheHandler;

use Abivia\Geocode\GeocodeResult\IpStackResult;
use IPLib\Factory as IpAddressFactory;
use PHPUnit\Framework\TestCase;

class ArrayCacheTest extends TestCase
{
    public ArrayCache $testObj;

    public function setUp(): void
    {
        parent::setUp();
        $this->testObj = new ArrayCache();
    }

    public function testFetchOnEmpty()
    {
        $address = IpAddressFactory::parseAddressString('123.123.123.123');
        $fetched = $this->testObj->get($address);
        $this->assertNull($fetched);
        $fetched = $this->testObj->getSubNet($address);
        $this->assertNull($fetched);
    }

    public function testCacheHit()
    {
        $address = IpAddressFactory::parseAddressString('123.123.123.123');
        $fakeResult = new IpStackResult(['ip' => '123.123.123.123']);
        $this->testObj->set($address, $fakeResult);
        $result = $this->testObj->get($address);
        $this->assertNotNull($result);
        $this->assertTrue($result->cached());
        $subnetAddress = IpAddressFactory::parseAddressString('123.123.123.12');
        $result = $this->testObj->get($subnetAddress);
        $this->assertNull($result);
        $result = $this->testObj->getSubnet($subnetAddress);
        $this->assertNotNull($result);
        $this->assertTrue($result->cached());
        $this->assertEquals('123.123.123.123', $result->getIpAddress());
    }

    public function testCacheExpiry()
    {
        $cacheObj = new ArrayCache();
        $cacheObj->hitCacheTime(4);
        $cacheObj->missCacheTime(2);
        $hitAddress = IpAddressFactory::parseAddressString('123.123.123.123');
        $fakeResult = new IpStackResult(['ip' => '123.123.123.123']);
        $cacheObj->set($hitAddress, $fakeResult);
        $missAddress = IpAddressFactory::parseAddressString('1.1.1.1');
        $cacheObj->set($missAddress, null);
        $cacheObj->get($hitAddress);
        $this->assertTrue($cacheObj->hit());
        $cacheObj->get($missAddress);
        $this->assertTrue($cacheObj->hit());
        sleep(3);
        $this->assertNotNull($cacheObj->get($hitAddress));
        $this->assertNull($cacheObj->get($missAddress));
        $this->assertFalse($cacheObj->hit());
        sleep(2);
        $this->assertNull($cacheObj->get($hitAddress));
        $this->assertFalse($cacheObj->hit());
        $this->assertNull($cacheObj->get($missAddress));
        $this->assertFalse($cacheObj->hit());
    }

}
