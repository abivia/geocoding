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
class IpStackApi extends AbstractService
{
    /**
     * @var string API access key
     */
    protected string $accessKey;

    /**
     * @var string Default API base URL (https only on paid plan)
     */
    protected string $baseUrl;

    /**
     * @var array paid/free API endpoints
     */
    protected array $baseUrlMap = [
        'free' => 'http://api.ipstack.com',
        'paid' => 'https://api.ipstack.com',
    ];

    /**
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        parent::__construct($config);
        $this->accessKey = $config['key'] ?? '';
        $this->baseUrl = $this->baseUrlMap[$this->accessKey === '' ? 'free' : 'paid'];
    }

    /**
     * Look up the current address via the IPStack API.
     *
     * @param string $address A v4 or v6 IP address.
     * @return array|null
     * @throws LookupFailedException
     */
    public function queryCore(string $address): ?IpStackResult
    {
        $url = "$this->baseUrl/$address?" . http_build_query(['access_key' => $this->accessKey]);
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
                $error = $response['error'];
                throw new LookupFailedException("{$error['type']}: {$error['info']}");
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
