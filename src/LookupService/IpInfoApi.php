<?php

namespace Abivia\Geocode\LookupService;

use Abivia\Geocode\GeocodeResult\IpInfoResult;
use Abivia\Geocode\LookupFailedException;
use Carbon\Carbon;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\CacheItem;
use Symfony\Contracts\Cache\ItemInterface;

class IpInfoApi extends AbstractService implements LookupService
{
    protected const int NEW_MONTH_DELAY = 24 * 3600;
    /**
     * @var array The order to use querying the APIs (eg. [core, lite, free])
     */
    protected array $apiOrder;

    /**
     * @var array Base URLs for each service level
     */
    protected array $baseUrl = [
        'free' => 'https://ipinfo.io/$i/json',
        'lite' => 'https://api.ipinfo.io/lite/$i',
        'core' => 'https://api.ipinfo.io/lookup/$i',
    ];

    /**
     * @var string API access token
     */
    protected string $token;

    /**
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        parent::__construct($config);
        $this->token = $config['key'];
        $apiList = $config['apiList'] ?? ['lite', 'free'];
        $this->apiOrder = is_array($apiList) ? $apiList : [$apiList];
    }

    /**
     * Query the ipinfo.io API (or APIs) for a lookup result.
     * @param string $address
     * @param int $apiSlot
     * @return IpInfoResult|null
     * @throws InvalidArgumentException
     * @throws LookupFailedException
     */
    protected function queryCore(string $address, int $apiSlot = -1): ?IpInfoResult
    {
        $apiCount = count($this->apiOrder);
        if ($apiCount === 0) {
            throw new LookupFailedException('List of APIs to check is empty.');
        }
        $cacheKey = $this->cacheKey(__CLASS__ . '.' . $this->apiOrder[0]);
        if ($apiSlot === -1) {
            // Determine which API we're going to use. If we have the result cached,
            // check that first.
            if ($this->cache && $apiCount > 1) {
                for ($apiSlot = 0; $apiSlot < $apiCount - 1; $apiSlot++) {
                    $cacheKey = $this->cacheKey(__CLASS__ . '.' . $this->apiOrder[$apiSlot]);
                    if (!$this->cache->hasItem($cacheKey)) {
                        break;
                    }
                }
            } else {
                // Nothing cached, try them in order.
                $apiSlot = 0;
            }
        }
        $apiName = strtolower($this->apiOrder[$apiSlot]);
        $url = $this->baseUrl[$apiName];
        $url = str_replace('$i', $address, $url);
        if ($this->token !== '') {
            $url .= '?' . http_build_query(['token' => $this->token]);
        }
        $this->providerLookup($url);
        if ($this->lookupHttpCode === 429) {
            // This API is exhausted for the current month.
            // Cache that if we can so we don't try to use it until it opens up again.
            if ($this->cache) {
                $resetDate = Carbon::parse('first day of next month')
                    ->setTime(0, 0)
                    ->addSeconds($this->config['newMonth'] ?? self::NEW_MONTH_DELAY);
                $cacheLife = (int) new Carbon()->diffInSeconds($resetDate);
                $item = $this->cache->getItem(
                    $this->cacheKey($cacheKey)
                );
                $item->expiresAfter($cacheLife);
                $item->set(true);
                $this->cache->save($item);
            }
            if (++$apiSlot < $apiCount) {
                return $this->queryCore($address, $apiSlot);
            } else {
                throw new LookupFailedException("Too many requests on all selected APIs.");
            }
        } elseif ($this->lookupHttpCode !== 200) {
            throw new LookupFailedException("HTTP Error on request $this->lookupHttpCode");
        } elseif (is_string($this->lookupResult)) {
            $response = json_decode($this->lookupResult, true);
            if ($response === null) {
                throw new LookupFailedException("Response was not valid JSON on $apiName API.");
            }
            if ($response['error'] ?? false) {
                throw new LookupFailedException(
                    "{$response['error']['title']} on $apiName API: {$response['error']['message']}"
                );
            }
            $response['_api'] = $apiName;
            return new IpInfoResult($response);
        }
        return null;
    }

    public function setUrl(string $level, string $url): self
    {
        if ($this->baseUrl[$level] ?? false) {
            $this->baseUrl[$level] = $url;
        }

        return $this;
    }

}
