<?php

use Abivia\Geocode\LookupService\IpInfoApi;

/**
 * IpInfoApi with providerLookup() stubbed out so the lite/free/core fallback
 * logic can be exercised without hitting the network. Responses are supplied
 * per API tier and matched against the real $baseUrl templates, so the
 * double keeps working if those URLs change.
 */
class IpInfoApiFallbackDouble extends IpInfoApi
{
    /** @var array<string, array{0:int,1:string}> tier => [httpCode, body] */
    public array $responses = [];

    /** @var string[] Tiers queried, in call order */
    public array $calledTiers = [];

    protected function providerLookup(string $url, array $options = []): int
    {
        $tier = $this->tierForUrl($url);
        $this->calledTiers[] = $tier;
        if (!isset($this->responses[$tier])) {
            throw new RuntimeException("No stubbed response for tier '$tier' ($url)");
        }
        [$this->lookupHttpCode, $this->lookupResult] = $this->responses[$tier];
        return $this->lookupHttpCode;
    }

    private function tierForUrl(string $url): string
    {
        foreach ($this->baseUrl as $tier => $template) {
            $prefix = strstr($template, '$i', true);
            if ($prefix !== false && str_starts_with($url, $prefix)) {
                return $tier;
            }
        }
        return 'unknown';
    }
}