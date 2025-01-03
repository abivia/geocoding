<?php

namespace Abivia\Geocode\CacheHandler;

use Abivia\Geocode\Geocoder;
use Abivia\Geocode\GeocodeResult\GeocodeResult;
use IPLib\Address\AddressInterface;
use function time;

/**
 * The array cache prevents multiple lookups for the same address/subnet in a single
 * request/session.
 */
class ArrayCache extends AbstractCache implements CacheHandler
{
    protected array $cache = [];
    protected array $subnets = [];

    /**
     * @inheritDoc
     */
    public function get(AddressInterface $address): ?GeocodeResult
    {
        return $this->lookup($address->getComparableString());
    }

    /**
     * @inheritDoc
     */
    public function getSubnet(AddressInterface $address): ?GeocodeResult
    {
        $reference = $this->subnets[Geocoder::getSubnetAddress($address)] ?? null;
        if ($reference === null) {
            return null;
        }
        return $this->lookup($reference);
    }

    /**
     * Look for an address and return it if found.
     *
     * @param string $fullAddress The full IP address to look up
     * @return GeocodeResult|null
     */
    protected function lookup(string $fullAddress): ?GeocodeResult
    {
        $this->hit = false;
        $hit = $this->cache[$fullAddress] ?? null;
        if ($hit === null) {
            return null;
        }
        if ($hit['expires'] < time()) {
            unset($this->cache[$fullAddress]);
            return null;
        }
        $this->hit = true;
        if ($hit['data'] !== null) {
            $result = clone $hit['data'];
            $result->cached(true);
        } else {
            $result = null;
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function set(AddressInterface $address, ?GeocodeResult $data): void
    {
        $fullAddress = $address->getComparableString();
        $expires = time() + ($data === null ? $this->missTime : $this->hitTime);
        $this->cache[$fullAddress] = ['data' => $data, 'expires' => $expires];
        $this->subnets[Geocoder::getSubnetAddress($address)] = $fullAddress;
    }

}
