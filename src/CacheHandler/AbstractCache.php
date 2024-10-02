<?php

namespace Abivia\Geocode\CacheHandler;

use Abivia\Geocode\GeocodeResult\GeocodeResult;
use IPLib\Address\AddressInterface;
use IPLib\Address\IPv4;

abstract class AbstractCache implements CacheHandler
{
    protected bool $hit;
    protected int $hitTime = 3600;
    protected int $missTime = 3600;

    /**
     * Look up an IP address.
     *
     * @param AddressInterface $address The source address to look up.
     * @return GeocodeResult|null Geocode data on the address or null if nothing found.
     */
    abstract public function get(AddressInterface $address): ?GeocodeResult;

    /**
     * Look up an IP address subnet.
     *
     * @param AddressInterface $address The source address to look up.
     * @return GeocodeResult|null Geocode data on the subnet address or null if nothing found.
     */
    abstract public function getSubnet(AddressInterface $address): ?GeocodeResult;

    public function hit(): bool
    {
        return $this->hit;
    }

    /**
     * Set the time that a successful lookup will be cached.
     * @param int|null $secs Retention time in seconds.
     * @return int
     */
    public function hitCacheTime(?int $secs = null): int
    {
        if ($secs !== null) {
            $this->hitTime = $secs;
        }
        return $this->hitTime;
    }

    /**
     * Set the time that an unsuccessful lookup will be cached.
     * @param int|null $secs Retention time in seconds.
     * @return int
     */
    public function missCacheTime(?int $secs = null): int
    {
        if ($secs !== null) {
            $this->missTime = $secs;
        }
        return $this->missTime;
    }

    /**
     * Save a lookup result into the cache.
     * @param AddressInterface $address The address that was looked up.
     * @param GeocodeResult|null $data The (non-)result of the lookup
     * @return void
     */
    abstract public function set(AddressInterface $address, ?GeocodeResult $data): void;

    /**
     * Get the subnet address from a full address.
     * @param AddressInterface $address The full address.
     * @return string The subnet part of the address.
     */
    protected function subnetAddress(AddressInterface $address): string
    {
        $fullAddress = $address->getComparableString();
        if ($address instanceof IPv4) {
            $subnet = substr($fullAddress, 0, 11);
        } else {
            $subnet = substr($fullAddress, 0, 14);
        }
        return $subnet;
    }

}
