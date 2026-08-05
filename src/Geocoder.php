<?php
declare(strict_types=1);

namespace Abivia\Geocode;

use Abivia\Geocode\GeocodeResult\GeocodeResult;
use Abivia\Geocode\LookupService\LookupService;
use InvalidArgumentException;
use IPLib\Address\AddressInterface;
use IPLib\Address\IPv4;
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
     * @var GeocodeResult|null Result from a lookup on IP address
     */
    protected ?GeocodeResult $geoData;

    /**
     * @var AddressInterface|null The current IP address
     */
    protected AddressInterface|null $ipAddress = null;

    protected static array $knownProxyHeaders = [
        'HTTP_CF_CONNECTING_IP',
        'HTTP_CLIENT_IP',
        'HTTP_X_FORWARDED_FOR',
    ];

    protected array $proxyList = [
        'HTTP_X_FORWARDED_FOR',
    ];

    /**
     * @param LookupService $service
     */
    public function __construct(LookupService $service)
    {
        $this->apiService = $service;
    }

    /**
     * Add a custom proxy header by name.
     * @param string $headerName
     * @return $this
     */
    public function addProxyHeader(string $headerName): self
    {
        $headerName = strtoupper($headerName);
        $this->removeProxyHeader($headerName);
        array_unshift($this->proxyList, $headerName);
        return $this;
    }

    /**
     * Add commonly used proxy headers to the known proxy list.
     * @return $this
     */
    public function addKnownProxyHeaders(): self
    {
        foreach (array_reverse(self::$knownProxyHeaders) as $headerName) {
            $this->addProxyHeader($headerName);
        }
        return $this;
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
     * @param bool $allowForward If set, the X_FORWARDED_FOR header can provide the address.
     * @param array|null $server Server environment, if not set, then $_SERVER is used.
     * @param array|null $ProxyHeaders A list of proxy client headers to check.
     * @return AddressInterface
     * @throws AddressNotFoundException
     */
    public static function getAddressFromHttp(
        bool $allowForward = true,
        ?array $server = null,
        ?array $ProxyHeaders = null,
    ): AddressInterface {
        $server ??= $_SERVER;
        $source = null;
        $ipAddress = null;
        if ($allowForward ) {
            foreach ($ProxyHeaders ?? self::$knownProxyHeaders as $headerName) {
                if (isset($server[$headerName])) {
                    $source = htmlspecialchars($server[$headerName]);
                    $ipAddress = IpAddressFactory::parseAddressString($source);
                    if ($ipAddress !== null) {
                        break;
                    }
                }
            }
        }
        if ($ipAddress === null && isset($server['REMOTE_ADDR'])) {
            $source = htmlspecialchars($server['REMOTE_ADDR']);
            $ipAddress = IpAddressFactory::parseAddressString($source);
        }
        if ($source === null) {
            throw new AddressNotFoundException('No address found in server super-global.');
        }
        if ($ipAddress === null) {
            throw new AddressNotFoundException("Failed to parse $source as an IP address.");
        }
        return $ipAddress;
    }

    public function getApiService(): LookupService
    {
        return $this->apiService;
    }

    /**
     * Get the subnet address from a full address.
     * @param AddressInterface $address The full address.
     * @return string The subnet part of the address.
     */
    public static function getSubnetAddress(AddressInterface $address): string
    {
        $fullAddress = $address->getComparableString();
        if ($address instanceof IPv4) {
            $subnet = substr($fullAddress, 0, 11);
        } else {
            $subnet = substr($fullAddress, 0, 14);
        }
        return $subnet;
    }

    /**
     * Get geocoding data for an IP address.
     *
     * @param string|null $address If null, then the current address set is used.
     * @return GeocodeResult|null
     * @throws InvalidArgumentException
     */
    public function lookup(?string $address = null): ?GeocodeResult
    {
        if ($address !== null) {
            $this->address($address);
        }
        $this->geoData = $this->apiService->query($this->ipAddress->toString());
        return $this->geoData;
    }

    /**
     * Do a lookup based on the current HTTP request.
     *
     * @param bool $allowForward
     * @return  GeocodeResult|null
     * @throws AddressNotFoundException
     */
    public function lookupHttp(bool $allowForward = true): ?GeocodeResult
    {
        $this->ipAddress = static::getAddressFromHttp($allowForward, ProxyHeaders: $this->proxyList);
        return $this->lookup();
    }

    /**
     * Lookup the /24 subnet of an IP address, or the /48 of an IPv6 address.
     * @param string|null $address
     * @return GeocodeResult|null
     */
    public function lookupSubnet(?string $address = null): ?GeocodeResult
    {
        if ($address !== null) {
            $this->address($address);
        }
        $subNet = self::getSubnetAddress($this->ipAddress);
        $this->geoData = $this->apiService->query($subNet);
        return $this->geoData;
    }

    /**
     * Remove a header from the list of proxy headers.
     * @param string $headerName
     * @return $this
     */
    public function removeProxyHeader(string $headerName): self
    {
        $headerName = strtoupper($headerName);
        $index = array_search($headerName, $this->proxyList);
        if ($index !== false) {
            unset($this->proxyList[$index]);
        }
        return $this;
    }

}
