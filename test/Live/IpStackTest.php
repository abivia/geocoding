<?php


use Abivia\Geocode\Geocoder;
use Abivia\Geocode\LookupService\IpStackApi;
use PHPUnit\Framework\TestCase;


class IpStackTest extends TestCase
{
    public function testLookup()
    {
        $keyfile = dirname(dirname(__DIR__)) . '/.ipstack-key';
        if (!file_exists($keyfile)) {
            $this->fail("$keyfile file not found.");
        }
        $geocoder = new Geocoder(IpStackApi::make(trim(file_get_contents($keyfile))));
        $result = $geocoder->lookup('173.239.198.14');
        $this->assertNotNull($result);
        $this->assertEquals('US', $result->getCountryCode());
        $this->assertEquals('173.239.198.14', $result->getIpAddress());
    }

}
