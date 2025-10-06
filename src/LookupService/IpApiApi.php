<?php

namespace Abivia\Geocode\LookupService;

use Abivia\Geocode\GeocodeResult\IpApiResult;
use Abivia\Geocode\LookupFailedException;

class IpApiApi implements LookupService
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
     * @param string $accessKey
     */
    public function __construct(string $accessKey = '')
    {
        $this->accessKey = $accessKey;
    }

    public static function make(string $accessKey = ''): static
    {
        return new static($accessKey);
    }

    /**
     * Look up the current address via ipapi.co.
     *
     * @param string $address A v4 or v6 IP address.
     * @return array|null
     * @throws LookupFailedException
     */
    public function query(string $address): ?IpApiResult
    {
        if ($this->accessKey !== '') {
            $url = "$this->baseUrl$address/json?"
                . http_build_query([
                    'key' => $this->accessKey
                ]);
        } else {
            $url = "$this->baseUrl$address/json";
        }
        $channel = curl_init($url);
        curl_setopt($channel, CURLOPT_RETURNTRANSFER, true);
        $json = curl_exec($channel);
        curl_close($channel);
        if (is_string($json)) {
            $response = json_decode($json, true);
            if ($response === null) {
                throw new LookupFailedException("Response was not valid JSON.");
            }
            if ($response['error'] ?? false) {
                throw new LookupFailedException("{$response['reason']}: {$response['message']}");
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
