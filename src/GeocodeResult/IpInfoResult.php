<?php
declare(strict_types=1);

namespace Abivia\Geocode\GeocodeResult;

/**
 * Container for a result returned by ipinfo.io.
 */
class IpInfoResult extends GeocodeResult
{
    protected function normalize()
    {
        $this->normalized['ipAddress'] = $this->data['ip'];
        if ($this->data['_api'] === 'core') {
            $this->normalized['latitude'] = (float)trim($this->data['geo']['latitude'] ?? null);
            $this->normalized['longitude'] = (float)trim($this->data['geo']['longitude'] ?? null);
            $this->normalizeGeo($this->data['geo']);
            $this->normalized['asn'] = $this->data['as']['asn'] ?? null;
            $this->normalized['asnName'] = $this->data['as']['name'] ?? null;
            $this->normalized['asnDomain'] = $this->data['as']['domain'] ?? null;
            $this->normalized['asnType'] = $this->data['as']['type'] ?? null;
            $this->normalized['isAnonymous'] = $this->data['is_anonymous'];
            $this->normalized['isAnycast'] = $this->data['is_anycast'];
            $this->normalized['isHosting'] = $this->data['is_hosting'];
            $this->normalized['isMobile'] = $this->data['is_mobile'];
            $this->normalized['isSatellite'] = $this->data['is_satellite'];
            $this->normalized['isAnonymousProxy'] = $this->data['anonymous']['is_proxy'] ?? null;
            $this->normalized['isAnonymousRelay'] = $this->data['anonymous']['is_relay'] ?? null;
            $this->normalized['isAnonymousTor'] = $this->data['anonymous']['is_tor'] ?? null;
            $this->normalized['isAnonymousVpn'] = $this->data['anonymous']['is_vpn'] ?? null;
        } else {
            $this->parseLoc();
            $this->normalizeGeo($this->data);
            if ($this->data['_api'] === 'free') {
                // Free has the ASN and name in a single string.
                [$this->normalized['asn'], $this->normalized['asnName']]
                    = explode(' ', $this->data['org'], 2);
                $this->normalized['asnDomain'] = null;

                // Free returns the country code as country.
                $this->normalized['countryCode'] = $this->data['country'] ?? null;
            } else {
                $this->normalized['asn'] = $this->data['asn'] ?? null;
                $this->normalized['asnName'] = $this->data['as_name'] ?? null;
                $this->normalized['asnDomain'] = $this->data['as_domain'] ?? null;
            }
            $this->normalized['asnType'] = null;
            $this->normalized['isAnonymous'] = null;
            $this->normalized['isAnycast'] = null;
            $this->normalized['isHosting'] = null;
            $this->normalized['isMobile'] = null;
            $this->normalized['isSatellite'] = null;
            $this->normalized['isAnonymousProxy'] = null;
            $this->normalized['isAnonymousRelay'] = null;
            $this->normalized['isAnonymousTor'] = null;
            $this->normalized['isAnonymousVPN'] = null;
        }
    }

    private function normalizeGeo(?array $data = [])
    {
        $this->normalized['adminArea'] = $data['region'] ?? null;
        $this->normalized['adminAreaCode'] = $data['region_code'] ?? null;
        $this->normalized['continent'] = $data['continent'] ?? null;
        $this->normalized['continentCode'] = $data['continent_code'] ?? null;
        $this->normalized['country'] = $data['country'] ?? null;
        $this->normalized['countryCode2'] = $data['countryCode'] ?? null;
        $this->normalized['locality'] = $data['city'] ?? null;
        $this->normalized['postalCode'] = $data['postal_code'] ?? ($data['postal'] ?? null);
        $this->normalized['timezone'] = $data['timezone'] ?? null;
    }

    private function parseLoc()
    {
        if (isset($this->data['loc'])) {
            $parts = explode(',', $this->data['loc']);
            $this->normalized['latitude'] = (float)trim($parts[0]);
            $this->normalized['longitude'] = (float)trim($parts[1]);
        }
    }

}
