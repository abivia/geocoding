<?php

namespace Abivia\Geocode\CacheHandler;

use Abivia\Geocode\Geocoder;
use Abivia\Geocode\GeocodeResult\GeocodeResult;
use IPLib\Address\AddressInterface;

abstract class AbstractCache implements CacheHandler
{
    protected bool $hit;
    /**
     * @var int Default time to retain a successful lookup in cache
     */
    protected int $hitTime = 3600;
    /**
     * @var int Default time to retain a failed lookup in cache
     */
    protected int $missTime = 3600;
    /**
     * @var int Default purge time
     */
    protected int $purgeTime = 24 * 3600;

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
     * Set the time that an unsuccessful lookup will be cached.
     * @param int|null $secs Retention time in seconds.
     * @return int
     */
    public function purgeCacheTime(?int $secs = null): int
    {
        if ($secs !== null) {
            $this->purgeTime = $secs;
        }
        return $this->purgeTime;
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
        return Geocoder::getSubnetAddress($address);
    }

}
