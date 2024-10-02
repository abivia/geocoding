<?php

namespace Abivia\Geocode\CacheHandler;

use Abivia\Geocode\GeocodeResult\IpStackResult;
use IPLib\Factory as IpAddressFactory;
use PDO;
use PHPUnit\Framework\TestCase;

class SqliteCacheTest extends TestCase
{
    const DB_NAME = 'pdo.sqlite';

    public CacheHandler $cacheObj;
    public PDO $db;

    protected function dumpIpTable(): void
    {
        echo "IP Table at " . time() . "\n";
        foreach($this->db->query("SELECT * FROM `geocoder_cache_ip`", PDO::FETCH_NUM) as $row) {
            $row[1] = md5($row[1]);
            $row[3] = ($row[2] - time())/3600.0;
            echo implode(', ', $row) . "h\n";
        }
        echo "\n";
    }

    public function setUp(): void
    {
        @unlink(__DIR__ . '/' . self::DB_NAME);
        $this->db = new PDO('sqlite:' . __DIR__ . '/' . self::DB_NAME);
        $this->cacheObj = new PdoCache($this->db);
    }

    public function tearDown(): void
    {
        @unlink(__DIR__ . '/' . self::DB_NAME);
    }

    public function testFetchOnEmpty()
    {
        $address = IpAddressFactory::parseAddressString('123.123.123.123');
        $fetched = $this->cacheObj->get($address);
        $this->assertNull($fetched);
        $fetched = $this->cacheObj->getSubNet($address);
        $this->assertNull($fetched);
    }

    public function testCacheHit()
    {
        $address = IpAddressFactory::parseAddressString('123.123.123.123');
        $fakeResult = new IpStackResult(['ip' => '123.123.123.123']);
        $this->cacheObj->set($address, $fakeResult);
        $result = $this->cacheObj->get($address);
        $this->assertNotNull($result);
        $this->assertTrue($result->cached());
        $subnetAddress = IpAddressFactory::parseAddressString('123.123.123.12');
        $result = $this->cacheObj->get($subnetAddress);
        $this->assertNull($result);
        $result = $this->cacheObj->getSubnet($subnetAddress);
        $this->assertNotNull($result);
        $this->assertTrue($result->cached());
        $this->assertEquals('123.123.123.123', $result->getIpAddress());
    }

    public function testCacheExpiry()
    {
        $this->cacheObj->hitCacheTime(4);
        $this->cacheObj->missCacheTime(2);
        $hitAddress = IpAddressFactory::parseAddressString('123.123.123.123');
        $fakeResult = new IpStackResult(['ip' => '123.123.123.123']);
        $this->cacheObj->set($hitAddress, $fakeResult);
        $missAddress = IpAddressFactory::parseAddressString('1.1.1.1');
        $this->cacheObj->set($missAddress, null);
        $this->cacheObj->get($hitAddress);
        $this->assertTrue($this->cacheObj->hit());
        $this->cacheObj->get($missAddress);
        $this->assertTrue($this->cacheObj->hit());
        sleep(3);
        $this->assertNotNull($this->cacheObj->get($hitAddress));
        $this->assertNull($this->cacheObj->get($missAddress));
        $this->assertFalse($this->cacheObj->hit());
        sleep(2);
        $this->assertNull($this->cacheObj->get($hitAddress));
        $this->assertFalse($this->cacheObj->hit());
        $this->assertNull($this->cacheObj->get($missAddress));
        $this->assertFalse($this->cacheObj->hit());
    }

    public function testReload()
    {
        $address = IpAddressFactory::parseAddressString('123.123.123.123');
        $fakeResult = new IpStackResult(['ip' => '123.123.123.123']);
        $this->cacheObj->set($address, $fakeResult);
        $result = $this->cacheObj->get($address);
        unset($this->cacheObj);
        // Reload
        $this->cacheObj = new PdoCache($this->db);
        $this->assertTrue($result->cached());
        $subnetAddress = IpAddressFactory::parseAddressString('123.123.123.12');
        $result = $this->cacheObj->get($subnetAddress);
        $this->assertNull($result);
        $result = $this->cacheObj->getSubnet($subnetAddress);
        $this->assertNotNull($result);
        $this->assertTrue($result->cached());
        $this->assertEquals('123.123.123.123', $result->getIpAddress());
    }

}
