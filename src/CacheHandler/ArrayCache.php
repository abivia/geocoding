<?php

namespace Abivia\Geocode\CacheHandler;

use Abivia\Geocode\GeocodeResult\GeocodeResult;
use IPLib\Address\AddressInterface;
use IPLib\Address\IPv4;
use function time;

/**
 * The array cache prevents multiple lookups for the same address/subnet in a single
 * request/session.
 */
class ArrayCache implements CacheHandler
{
    protected array $cache = [];
    protected bool $hit;
    protected int $hitCacheTime = 3600;
    protected int $missCacheTime = 3600;
    protected array $subnets = [];

    public function get(AddressInterface $address): ?GeocodeResult
    {
        return $this->lookup($address->getComparableString());
    }

    public function getSubnet(AddressInterface $address): ?GeocodeResult
    {
        $reference = $this->subnets[$this->subnetAddress($address)] ?? null;
        if ($reference === null) {
            return null;
        }
        return $this->lookup($reference);
    }

    public function hit(): bool
    {
        return $this->hit;
    }

    public function hitCacheTime(?int $secs = null): int
    {
        if ($secs !== null) {
            $this->hitCacheTime = $secs;
        }
        return $this->hitCacheTime;
    }

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

    public function missCacheTime(?int $secs = null): int
    {
        if ($secs !== null) {
            $this->missCacheTime = $secs;
        }
        return $this->missCacheTime;
    }

    public function set(AddressInterface $address, ?GeocodeResult $data)
    {
        $fullAddress = $address->getComparableString();
        $expires = time() + ($data === null ? $this->missCacheTime : $this->hitCacheTime);
        $this->cache[$fullAddress] = ['data' => $data, 'expires' => $expires];
        $this->subnets[$this->subnetAddress($address)] = $fullAddress;
    }

    protected function subnetAddress(AddressInterface $address): string
    {
        $fullAddress = $address->getComparableString();
        if ($address instanceof IPv4) {
            $Subnet = substr($fullAddress, 0, 11);
        } else {
            $Subnet = substr($fullAddress, 0, 14);
        }
        return $Subnet;
    }

}
