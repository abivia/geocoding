<?php
declare(strict_types=1);

namespace Abivia\Geocode\LookupService;

use Abivia\Geocode\GeocodeResult\IpStackResult;
use function curl_close;
use function curl_exec;
use function curl_init;
use function curl_setopt;

/**
* Query the ipstack.com API
*
* @link    https://github.com/abivia/geocode
*/
class IpStackApi implements LookupService
{
    /**
     * @var string API access key
     */
    protected string $accessKey;

    /**
     * @var string Default API base URL (https only on paid plan)
     */
    protected string $baseUrl = 'http://api.ipstack.com';

    /**
     * @param string $accessKey
     */
    public function __construct(string $accessKey)
    {
        $this->accessKey = $accessKey;
    }

    static public function make(string $accessKey): static
    {
        return new static($accessKey);
    }

    /**
     * Look up the current address via the IPStack API.
     *
     * @param string $address A v4 or v6 IP address.
     * @return array|null
     */
    public function query(string $address): ?IpStackResult
    {
        $url = "$this->baseUrl/$address?" . http_build_query(['access_key' => $this->accessKey]);
        $channel = curl_init($url);
        curl_setopt($channel, CURLOPT_RETURNTRANSFER, true);
        $json = curl_exec($channel);
        curl_close($channel);
        if (is_string($json)) {
            return new IpStackResult(json_decode($json, true));
        }
        return null;
    }

    public function setUrl(string $url): self
    {
        $this->baseUrl = $url;

        return $this;
    }

}
