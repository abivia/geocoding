<?php

namespace Abivia\Geocode\LookupService;

use Abivia\Geocode\Geocoder;
use Abivia\Geocode\GeocodeResult\GeocodeResult;
use Abivia\Geocode\LookupFailedException;
use IPLib\Factory as IpAddressFactory;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Contracts\Cache\ItemInterface;

abstract class AbstractService implements LookupService
{
    public const int DEFAULT_TTL = 24 * 3600;
    public const int MISS_TTL = 6 * 3600;

    protected ?AdapterInterface $cache = null;

    protected array $config = [];

    protected int $lookupHttpCode;
    protected string|bool $lookupResult;

    /**
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    /**
     * Replace reserved PSR-6 key characters ({}()/\@:) with underscore.
     * @param string $base
     * @return string
     */
    public function cacheKey(string $base): string
    {
        return preg_replace('/[^a-z0-9._]/i', '_', $base);
    }

    public static function make(array $config = [], ?AdapterInterface $cache = null): static
    {
        $service = new static($config);
        if ($cache !== null) {
            $service->setCache($cache);
        }
        return $service;
    }

    protected function providerLookup(string $url, array $options = []): int
    {
        $channel = curl_init($url);
        if (count($options)) {
            curl_setopt_array($channel, $options);
        }
        curl_setopt($channel, CURLOPT_RETURNTRANSFER, true);
        $this->lookupResult = curl_exec($channel);
        $this->lookupHttpCode = curl_getinfo($channel, CURLINFO_HTTP_CODE);
        curl_close($channel);
        return $this->lookupHttpCode;
    }

    /**
     * Look up the current address via the provider's API.
     *
     * @param string $address A v4 or v6 IP address.
     * @return GeocodeResult|null
     * @throws LookupFailedException|InvalidArgumentException
     */
    public function query(string $address): ?GeocodeResult
    {
        if ($this->cache) {
            try {
                $cached = true;
                $lookup = $this->cache->get(
                    $this->cacheKey(static::class . '.' . $address),
                    function (ItemInterface $item) use ($address, &$cached) : ?GeocodeResult {
                        $lookup = $this->queryCore($address);
                        $cached = false;
                        $ttl = ($lookup === null)
                            ? ($this->config['missTtl'] ?? self::MISS_TTL)
                            : ($this->config['ttl'] ?? self::DEFAULT_TTL);
                        $item->expiresAfter($ttl);
                        $item->set($lookup);
                        return $lookup;
                    }
                );
                if ($lookup !== null) {
                    $lookup->cached($cached);
                    if (!$cached) {
                        $subNet = Geocoder::getSubnetAddress(
                            IpAddressFactory::parseAddressString($address)
                        );
                        $item = $this->cache->getItem(
                            $this->cacheKey($this->cacheKey(static::class . '.' . $subNet))
                        );
                        $item->expiresAfter($this->config['ttl'] ?? self::DEFAULT_TTL);
                        $item->set($lookup);
                        $this->cache->save($item);
                    }
                }
            } catch (InvalidArgumentException) {
                $lookup = null;
            }
        } else {
            $lookup = $this->queryCore($address);
        }
        return $lookup;
    }

    abstract protected function queryCore(string $address): ?GeocodeResult;

    public function setCache(AdapterInterface $cache): self
    {
        $this->cache = $cache;
        return $this;
    }
}
