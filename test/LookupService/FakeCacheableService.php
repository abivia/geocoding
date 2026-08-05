<?php

use Abivia\Geocode\GeocodeResult\GeocodeResult;
use Abivia\Geocode\GeocodeResult\IpStackResult;
use Abivia\Geocode\LookupService\AbstractService;

/**
 * A minimal AbstractService implementation for exercising the Symfony-cache
 * wrapper in AbstractService::query() without any network access.
 */
class FakeCacheableService extends AbstractService
{
    /** @var array<string, array|null> Address => raw provider data (null = not found) */
    public ?array $data = null;

    /** @var int Number of times queryCore() actually ran (i.e. cache misses) */
    public int $queryCoreCalls = 0;

    protected function queryCore(string $address): ?GeocodeResult
    {
        ++$this->queryCoreCalls;
        $raw = $this->data[$address] ?? null;
        return $raw === null ? null : new IpStackResult($raw);
    }
}
