<?php


use Abivia\Geocode\Geocoder;
use Abivia\Geocode\LookupService\IpInfoApi;
use PHPUnit\Framework\TestCase;


class IpInfoTest extends TestCase
{
    public function testLookup()
    {
        $geocoder = new Geocoder(IpInfoApi::make());
        $result = $geocoder->lookup('173.239.198.14');
        $this->assertNotNull($result);
        $this->assertEquals('US', $result->countryCode());
        $this->assertEquals('173.239.198.14', $result->ipAddress());
    }

}
