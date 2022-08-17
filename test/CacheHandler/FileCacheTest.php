<?php

namespace Abivia\Geocode\CacheHandler;

use Abivia\Geocode\GeocodeResult\IpStackResult;
use IPLib\Factory as IpAddressFactory;
use PHPUnit\Framework\TestCase;

class FileCacheTest extends TestCase
{
    public function testFetchOnEmpty()
    {
        @unlink(__DIR__ . '/filecache.json');
        $cacheObj = new FileCache(__DIR__ . '/filecache.json');
        $address = IpAddressFactory::parseAddressString('123.123.123.123');
        $fetched = $cacheObj->get($address);
        $this->assertNull($fetched);
        $fetched = $cacheObj->getSubNet($address);
        $this->assertNull($fetched);
    }

    public function testCacheHit()
    {
        $cacheObj = new FileCache(__DIR__ . '/filecache.json');
        $address = IpAddressFactory::parseAddressString('123.123.123.123');
        $fakeResult = new IpStackResult(['ip' => '123.123.123.123']);
        $cacheObj->set($address, $fakeResult);
        $result = $cacheObj->get($address);
        $this->assertNotNull($result);
        $this->assertTrue($result->cached());
        $subnetAddress = IpAddressFactory::parseAddressString('123.123.123.12');
        $result = $cacheObj->get($subnetAddress);
        $this->assertNull($result);
        $result = $cacheObj->getSubnet($subnetAddress);
        $this->assertNotNull($result);
        $this->assertTrue($result->cached());
        $this->assertEquals('123.123.123.123', $result->getIpAddress());
    }

    public function testCacheExpiry()
    {
        $cacheObj = new FileCache(__DIR__ . '/filecache.json');
        $cacheObj->hitCacheTime(4);
        $cacheObj->missCacheTime(2);
        $hitAddress = IpAddressFactory::parseAddressString('123.123.123.123');
        $fakeResult = new IpStackResult(['ip' => '123.123.123.123']);
        $cacheObj->set($hitAddress, $fakeResult);
        $missAddress = IpAddressFactory::parseAddressString('1.1.1.1');
        $cacheObj->set($missAddress, null);
        unset($cacheObj);
        $cacheObj = new FileCache(__DIR__ . '/filecache.json');
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

    public function testReload()
    {
        @unlink(__DIR__ . '/filecache.json');
        $cacheObj = new FileCache(__DIR__ . '/filecache.json');
        $address = IpAddressFactory::parseAddressString('123.123.123.123');
        $fakeResult = new IpStackResult(['ip' => '123.123.123.123']);
        $cacheObj->set($address, $fakeResult);
        $result = $cacheObj->get($address);
        unset($cacheObj);
        $this->assertTrue(file_exists(__DIR__ . '/filecache.json'));
        // Reload
        $cacheObj = new FileCache(__DIR__ . '/filecache.json');
        $this->assertNotNull($result);
        $this->assertTrue($result->cached());
        $subnetAddress = IpAddressFactory::parseAddressString('123.123.123.12');
        $result = $cacheObj->get($subnetAddress);
        $this->assertNull($result);
        $result = $cacheObj->getSubnet($subnetAddress);
        $this->assertNotNull($result);
        $this->assertTrue($result->cached());
        $this->assertEquals('123.123.123.123', $result->getIpAddress());
    }

}
