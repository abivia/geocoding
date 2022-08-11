<?php

namespace Abivia\Geocode\CacheHandler;

use Abivia\Geocode\GeocodeResult\GeocodeResult;
use IPLib\Address\AddressInterface;

interface CacheHandler
{
    public function get(AddressInterface $address): ?GeocodeResult;

    public function getSubnet(AddressInterface $address): ?GeocodeResult;

    public function hitCacheTime(?int $secs): int;

    public function missCacheTime(?int $secs): int;

    public function set(AddressInterface $address, GeocodeResult $data);

}
