<?php

namespace Abivia\Geocode\GeocodeResult;

use Abivia\Cogs\AddressProperties;

abstract class GeocodeResult implements AddressProperties
{
    /**
     * @var array|null The raw data as supplied by the geolocation provider.
     */
    protected ?array $data;

    protected bool $fromCache = false;

    /**
     * @var array|null[] The provider's data with a normalized naming convention.
     */
    protected array $normalized = [
        'addressLine1' => null,
        'addressLine2' => null,
        'adminArea' => null,                // State/province/etc.
        'adminAreaCode' => null,            // Short code for the admin area
        'asn' => null,
        'asnDomain' => null,
        'asnName' => null,
        'asnType' => null,
        'asnUpdated' => null,
        'continent' => null,
        'continentCode' => null,
        'country' => null,
        'countryCode2' => null,             // ISO 3166-1 alpha-2
        'countryCode3' => null,             // ISO 3166-1 alpha-3
        'dependentLocality' => null,        // Sub-admin area: District/county/neighbourhood
        'geoNameId' => null,                // GeoName identifier from geonames.org
        'hostname' => null,
        'isAnonymous' => null,
        'isAnonymousProxy' => null,
        'isAnonymousRelay' => null,
        'isAnyCast' => null,
        'isCrawler' => null,
        'isHosting' => null,
        'isMobile' => null,
        'isSatellite' => null,
        'isTor' => null,
        'isVpn' => null,
        'ipAddress' => null,
        'ipVersion' => null,                // IPv4 or IPv6
        'languages' => null,                // An array when present
        'latitude' => null,                 // As a float
        'locale' => null,
        'locality' => null,                 // City/municipality
        'longitude' => null,                // As a float
        'phoneCode' => null,                // Country calling code
        'postalCode' => null,
        'postalSortCode' => null,           // Typically a mail routing code
        'timezone' => null,                 // Region/city
    ];

    public function __construct(?array $data)
    {
        $this->data = $data;
        $this->normalize();
    }

    public function cached(?bool $set = null): bool
    {
        if ($set !== null) {
            $this->fromCache = $set;
        }
        return $this->fromCache;
    }

    /**
     * @inheritDoc
     */
    public function getAddressLine1(): ?string
    {
        return $this->normalized['addressLine1'];
    }

    /**
     * @inheritDoc
     */
    public function getAddressLine2(): ?string
    {
        return $this->normalized['addressLine2'];
    }

    /**
     * @inheritDoc
     */
    public function getAdministrativeArea(): ?string
    {
        return $this->normalized['adminArea'];
    }

    /**
     * The administrative area (state, province, etc) as a code, if available.
     */
    public function getAdministrativeAreaCode(): ?string
    {
        return $this->normalized['adminAreaCode'];
    }

    public function getAsn(): ?string
    {
        return $this->normalized['asn'];
    }

    /**
     * The name of the country
     */
    public function getCountry(): ?string
    {
        return $this->normalized['country'];
    }

    /**
     * @inheritDoc
     */
    public function getCountryCode(): ?string
    {
        return $this->normalized['countryCode2'];
    }

    /**
     * Get either the provider's raw data or the normalized copy.
     *
     * @param bool $normalized
     * @return array|null[]|null
     */
    public function getData(bool $normalized = false): ?array
    {
        return $normalized ? $this->normalized : $this->data;
    }

    /**
     * @inheritDoc
     */
    public function getDependentLocality(): ?string
    {
        return $this->normalized['dependentLocality'];
    }

    public function getIpAddress(): string
    {
        return $this->normalized['ipAddress'];
    }

    public function getLatitude(): ?float
    {
        return $this->normalized['latitude'];
    }

    public function getLocale(): ?string
    {
        return $this->normalized['locale'];
    }

    /**
     * @inheritDoc
     */
    public function getLocality(): ?string
    {
        return $this->normalized['locality'];
    }

    public function getLongitude(): ?float
    {
        return $this->normalized['longitude'];
    }

    /**
     * @inheritDoc
     */
    public function getPostalCode(): ?string
    {
        return $this->normalized['postalCode'];
    }

    /**
     * @inheritDoc
     */
    public function getSortingCode(): ?string
    {
        return $this->normalized['postalCode'];
    }

    public function getTimezone(): ?string
    {
        return $this->normalized['timezone'];
    }

    abstract protected function normalize();

}
