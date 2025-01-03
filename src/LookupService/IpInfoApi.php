<?php

namespace Abivia\Geocode\LookupService;

use Abivia\Geocode\GeocodeResult\IpInfoResult;

class IpInfoApi implements LookupService
{
    /**
     * @var string API access token
     */
    protected string $token;

    /**
     * @var string Default API base URL (https only on paid plan)
     */
    protected string $baseUrl = 'https://ipinfo.io/';

    /**
     * @param string $token
     */
    public function __construct(string $token = '')
    {
        $this->token = $token;
    }

    public static function make(string $token = ''): static
    {
        return new static($token);
    }

    /**
     * Look up the current address via the ioinfo.io API.
     *
     * @param string $address A v4 or v6 IP address.
     * @return array|null
     */
    public function query(string $address): ?IpInfoResult
    {
        $url = "$this->baseUrl$address/json";
        if ($this->token !== '') {
            $url .= '?' . http_build_query(['token' => $this->token]);
        }
        $channel = curl_init($url);
        curl_setopt($channel, CURLOPT_RETURNTRANSFER, true);
        $json = curl_exec($channel);
        curl_close($channel);
        if (is_string($json)) {
            return new IpInfoResult(json_decode($json, true));
        }
        return null;
    }

    public function setUrl(string $url): self
    {
        $this->baseUrl = $url;

        return $this;
    }

}
