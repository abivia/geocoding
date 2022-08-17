<?php
declare(strict_types=1);

namespace Abivia\Geocode\GeocodeResult;

/**
 * Container for a result returned by ipstack.com.
 */
class IpStackResult implements GeocodeResult
{
    private ?array $data;

    private bool $fromCache = false;

    public function __construct(?array $data)
    {
        $this->data = $data;
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
        return null;
    }

    /**
     * @inheritDoc
     */
    public function getAddressLine2(): ?string
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function getAdministrativeArea(): ?string
    {
        return $this->data['region_name'] ?? null;
    }

    /**
     * The administrative area (state, province, etc) as a code, if available.
     */
    public function getAdministrativeAreaCode(): ?string
    {
        return $this->data['region_code'] ?? null;
    }

    /**
     * The name of the country
     */
    public function getCountry(): ?string
    {
        return $this->data['country_name'] ?? null;
    }

    /**
     * @inheritDoc
     */
    public function getCountryCode(): ?string
    {
        return $this->data['country_code'] ?? null;
    }

    public function getData(): ?array
    {
        return $this->data;
    }

    /**
     * @inheritDoc
     */
    public function getDependentLocality(): ?string
    {
        return null;
    }

    public function getIpAddress(): string
    {
        return $this->data['ip'];
    }

    public function getLatitude(): ?float
    {
        return $this->data['latitude'] ?? null;
    }

    /**
     * @inheritDoc
     */
    public function getLocale(): ?string
    {
        return 'en-US';
    }

    /**
     * @inheritDoc
     */
    public function getLocality(): ?string
    {
        return $this->data['city'] ?? null;
    }

    public function getLongitude(): ?float
    {
        return $this->data['longitude'] ?? null;
    }

    /**
     * @inheritDoc
     */
    public function getPostalCode(): ?string
    {
        return $this->data['zip'] ?? null;
    }

    /**
     * @inheritDoc
     */
    public function getSortingCode(): ?string
    {
        return null;
    }

    public function getTimezone(): ?string
    {
        return $this->data['time_zone']['id'] ?? null;
    }

}
