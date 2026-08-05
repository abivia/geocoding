<?php
declare(strict_types=1);

namespace Abivia\Geocode\GeocodeResult;

/**
 * Container for a result returned by ipapi.co.
 */
class IpApiResult extends GeocodeResult
{
    protected function normalize(): void
    {
        $this->normalized['adminArea'] = $this->data['region'] ?? null;
        $this->normalized['adminAreaCode'] = $this->data['region_name'] ?? null;
        $this->normalized['asn'] = $this->data['asn'] ?? null;
        $this->normalized['country'] = $this->data['country_name'] ?? null;
        $this->normalized['countryCode2'] = $this->data['country_code'] ?? null;
        $this->normalized['countryCode3'] = $this->data['country_code_iso3'] ?? null;
        $this->normalized['hostname'] = $this->data['hostname'] ?? null;
        $this->normalized['ipAddress'] = $this->data['ip'] ?? null;
        $this->normalized['latitude'] = $this->data['latitude'] ?? null;
        $this->normalized['locale'] = $this->data['languages'] ?? null;
        $this->normalized['locality'] = $this->data['city'] ?? null;
        $this->normalized['longitude'] = $this->data['longitude'] ?? null;
        $this->normalized['postalCode'] = $this->data['postal'];
        $this->normalized['timezone'] = $this->data['timezone'] ?? null;
    }

}
