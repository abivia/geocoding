<?php

namespace Abivia\Geocode\LookupService;

use Abivia\Geocode\GeocodeResult\IpApiResult;
use Abivia\Geocode\LookupFailedException;
use Symfony\Component\Cache\Adapter\AdapterInterface;

class IpApiApi extends AbstractService implements LookupService
{
    /**
     * @var string API access token
     */
    protected string $accessKey;

    /**
     * @var string Free base URL
     */
    protected string $baseUrl = 'https://ipapi.co/';

    /**
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        parent::__construct($config);
        $this->accessKey = $config['key'] ?? '';
    }

    /**
     * Look up the current address via ipapi.co.
     *
     * @param string $address A v4 or v6 IP address.
     * @return array|null
     * @throws LookupFailedException
     */
    public function queryCore(string $address): ?IpApiResult
    {
        if ($this->accessKey !== '') {
            $url = "$this->baseUrl$address/json?"
                . http_build_query([
                    'key' => $this->accessKey
                ]);
        } else {
            $url = "$this->baseUrl$address/json";
        }
        $this->providerLookup($url);
        if ($this->lookupHttpCode !== 200) {
            throw new LookupFailedException("HTTP Error on request $this->lookupHttpCode");
        }
        if (is_string($this->lookupResult)) {
            $response = json_decode($this->lookupResult, true);
            if ($response === null) {
                throw new LookupFailedException("Response was not valid JSON.");
            }
            if ($response['error'] ?? false) {
                $message = $response['message'] ?? '';
                throw new LookupFailedException("{$response['reason']} $message");
            }
            return new IpApiResult($response);
        }
        return null;
    }

    public function setUrl(string $url): self
    {
        $this->baseUrl = $url;

        return $this;
    }

}
