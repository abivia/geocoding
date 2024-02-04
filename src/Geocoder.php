<?php
declare(strict_types=1);

namespace Abivia\Geocode;

use Abivia\Geocode\CacheHandler\ArrayCache;
use Abivia\Geocode\CacheHandler\CacheHandler;
use Abivia\Geocode\GeocodeResult\GeocodeResult;
use Abivia\Geocode\LookupService\LookupService;
use InvalidArgumentException;
use IPLib\Address\AddressInterface;
use IPLib\Factory as IpAddressFactory;

/**
 * Geocoding support for IP addresses
 *
 * @link    https://github.com/abivia/geocode
 */
class Geocoder
{
    /**
     * @var LookupService Service to query the IPStack API.
     */
    protected LookupService $apiService;
    /**
     * @var CacheHandler Lookup cache.
     */
    protected CacheHandler $cache;
    /**
     * @var GeocodeResult|null Result from a lookup on IP address
     */
    protected ?GeocodeResult $geoData;
    /**
     * @var AddressInterface|null The current IP address
     */
    protected AddressInterface|null $ipAddress = null;

    /**
     * @param string|LookupService $service
     * @param CacheHandler|null $cache
     */
    public function __construct(LookupService $service, CacheHandler $cache = null)
    {
        $this->apiService = $service;
        $this->cache = $cache ?? new ArrayCache();
    }

    /**
     * Set the current IP address.
     *
     * @param string $ip
     * @return  self
     * @throws InvalidArgumentException
     */
    public function address(string $ip): self
    {
        $this->ipAddress = IpAddressFactory::parseAddressString($ip);
        if ($this->ipAddress === null) {
            throw new InvalidArgumentException("$ip is not a valid IP address.");
        }

        return $this;
    }

    /**
     * Attempt to get an IP address from the current HTTP request.
     *
     * @return AddressInterface
     * @throws AddressNotFoundException
     */
    static public function getAddressFromHttp(): AddressInterface
    {
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ipAddress = IpAddressFactory::parseAddressString($_SERVER['HTTP_X_FORWARDED_FOR']);
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ipAddress = IpAddressFactory::parseAddressString($_SERVER['REMOTE_ADDR']);
        } else {
            throw new AddressNotFoundException('No address found in server super-global.');
        }
        return $ipAddress;
    }

    public function getApiService(): LookupService
    {
        return $this->apiService;
    }

    /**
     * Get geocoding data for an IP address.
     *
     * @param string|null $address If null the current address set is used.
     * @return GeocodeResult|null
     * @throws InvalidArgumentException
     */
    public function lookup(string $address = null): ?GeocodeResult
    {
        if ($address !== null) {
            $this->address($address);
        }
        $this->geoData = $this->cache->get($this->ipAddress);
        if ($this->geoData === null) {
            $apiData = $this->apiService->query($this->ipAddress->toString());
            if ($apiData !== null) {
                $this->geoData = $apiData;
                $this->cache->set($this->ipAddress, $this->geoData);
            }
        }
        return $this->geoData;
    }

    /**
     * Do a lookup based on the current HTTP request.
     *
     * @return  GeocodeResult|null
     * @throws AddressNotFoundException
     */
    public function lookupHttp(): ?GeocodeResult
    {
        $this->ipAddress = static::getAddressFromHttp();
        return $this->lookup();
    }

    public function lookupSubnet(string $address = null): ?GeocodeResult
    {
        if ($address !== null) {
            $this->address($address);
        }
        $this->geoData = $this->cache->getSubnet($this->ipAddress);
        if ($this->geoData === null) {
            $apiData = $this->apiService->query($this->ipAddress->toString());
            if ($apiData !== null) {
                $this->geoData = $apiData;
                $this->cache->set($this->ipAddress, $this->geoData);
            }
        }
        return $this->geoData;
    }

}
