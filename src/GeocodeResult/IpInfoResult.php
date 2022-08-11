<?php
declare(strict_types=1);

namespace Abivia\Geocode\GeocodeResult;

/**
 * Container for a result returned by ipstack.com.
 */
class IpInfoResult implements GeocodeResult
{
    private ?array $data;

    protected ?float $latitude;

    protected ?float $longitude;

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
        return $this->data['region'] ?? null;
    }

    /**
     * The administrative area (state, province, etc) as a code, if available.
     */
    public function administrativeAreaCode(): ?string
    {
        return null;
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
        return $this->data['country'] ?? null;
    }

    /**
     * @inheritDoc
     */
    public function countryCode(): ?string
    {
        return $this->data['country'] ?? null;
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
        if (!isset($this->latitude)) {
            $this->parseLoc();
        }
        return $this->latitude;
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
        if (!isset($this->longitude)) {
            $this->parseLoc();
        }
        return $this->longitude;
    }

    private function parseLoc()
    {
        if (isset($this->data['loc'])) {
            $parts = explode(',', $this->data['loc']);
            $this->latitude = (float)trim($parts[0]);
            $this->longitude = (float)trim($parts[1]);
        } else {
            $this->latitude = null;
            $this->longitude = null;
        }
    }

    /**
     * @inheritDoc
     */
    public function postalCode(): ?string
    {
        return $this->data['postal'] ?? null;
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
        return $this->data['timezone'] ?? null;
    }

}
