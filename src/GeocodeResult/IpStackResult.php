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

    /**
     * @inheritDoc
     */
    public function addressLine1(): ?string
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function addressLine2(): ?string
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function administrativeArea(): ?string
    {
        return $this->data['region_name'] ?? null;
    }

    /**
     * The administrative area (state, province, etc) as a code, if available.
     */
    public function administrativeAreaCode(): ?string
    {
        return $this->data['region_code'] ?? null;
    }

    public function cached(?bool $set = null): bool
    {
        if ($set !== null) {
            $this->fromCache = $set;
        }
        return $this->fromCache;
    }

    /**
     * The name of the country
     */
    public function country(): ?string
    {
        return $this->data['country_name'] ?? null;
    }

    /**
     * @inheritDoc
     */
    public function countryCode(): ?string
    {
        return $this->data['country_code'] ?? null;
    }

    public function data(): ?array
    {
        return $this->data;
    }

    /**
     * @inheritDoc
     */
    public function dependentLocality(): ?string
    {
        return null;
    }

    public function ipAddress(): string
    {
        return $this->data['ip'];
    }

    public function latitude(): ?float
    {
        return $this->data['latitude'] ?? null;
    }

    /**
     * @inheritDoc
     */
    public function locale(): ?string
    {
        return 'en-US';
    }

    /**
     * @inheritDoc
     */
    public function locality(): ?string
    {
        return $this->data['city'] ?? null;
    }

    public function longitude(): ?float
    {
        return $this->data['longitude'] ?? null;
    }

    /**
     * @inheritDoc
     */
    public function postalCode(): ?string
    {
        return $this->data['zip'] ?? null;
    }

    /**
     * @inheritDoc
     */
    public function sortingCode(): ?string
    {
        return null;
    }

    public function timezone(): ?string
    {
        return $this->data['time_zone']['id'] ?? null;
    }

}
