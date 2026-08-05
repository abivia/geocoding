<?php
declare(strict_types=1);

namespace Abivia\Geocode\GeocodeResult;

/**
 * Container for a result returned by ipstack.com.
 */
class IpStackResult extends GeocodeResult
{
    protected function normalize()
    {
        $this->normalized['adminArea'] = $this->data['region_name'] ?? null;
        $this->normalized['adminAreaCode'] = $this->data['region_code'] ?? null;
        $this->normalized['asn'] = $this->data['connection']['asn'] ?? null;
        $this->normalized['country'] = $this->data['country_name'] ?? null;
        $this->normalized['countryCode2'] = $this->data['country_code'] ?? null;
        $this->normalized['ipAddress'] = $this->data['ip'] ?? null;
        $this->normalized['latitude'] = $this->data['latitude'] ?? null;
        $this->normalized['locality'] = $this->data['city'] ?? null;
        $this->normalized['longitude'] = $this->data['longitude'] ?? null;
        $this->normalized['postalCode'] = $this->data['zip'] ?? null;
        $this->normalized['timezone'] = $this->data['time_zone']['id'] ?? null;
        $security = $this->data['security'] ?? [];
        if ($security['is_proxy'] ?? false) {
            $this->normalized['isAnonymous'] = true;
            $this->normalized['isAnonymousProxy'] = true;
            switch ($this->data['proxy_type']) {
                case 'hosting':
                    $this->normalized['isHosting'] = true;
                    break;
                case 'VPN':
                    $this->normalized['isVpn'] = true;
                    break;
            }
        }
        $this->normalized['isCrawler'] = $this->data['is_crawler'] ?? null;
        $this->normalized['isTor'] = $this->data['is_tor'] ?? null;
    }
}
