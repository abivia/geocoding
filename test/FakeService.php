<?php

use Abivia\Geocode\GeocodeResult\IpStackResult;
use Abivia\Geocode\LookupService\LookupService;
use Abivia\Geocode\GeocodeResult\GeocodeResult;

class FakeService implements LookupService
{
    static $cache = [];

    public int $lookups = 0;

    public function query(string $address): ?GeocodeResult
    {
        if (count(self::$cache) === 0) {
            self::$cache = json_decode(file_get_contents(__DIR__ . '/ip.json'), true);
        }
        ++$this->lookups;
        foreach (self::$cache as $item) {
            if ($item['ip'] === $address) {
                return new IpStackResult($item);
            }
        }
        return null;
    }

}
