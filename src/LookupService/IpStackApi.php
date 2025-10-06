<?php
declare(strict_types=1);

namespace Abivia\Geocode\LookupService;

use Abivia\Geocode\GeocodeResult\IpStackResult;
use Abivia\Geocode\LookupFailedException;
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

    public static function make(string $accessKey): static
    {
        return new static($accessKey);
    }

    /**
     * Look up the current address via the IPStack API.
     *
     * @param string $address A v4 or v6 IP address.
     * @return array|null
     * @throws LookupFailedException
     */
    public function query(string $address): ?IpStackResult
    {
        $url = "$this->baseUrl/$address?" . http_build_query(['access_key' => $this->accessKey]);
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
                throw new LookupFailedException("{$response['type']}: {$response['info']}");
            }
            return new IpStackResult($response);
        }
        return null;
    }

    public function setUrl(string $url): self
    {
        $this->baseUrl = $url;

        return $this;
    }

}
