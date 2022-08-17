<?php
declare(strict_types=1);

namespace Abivia\Geocode\GeocodeResult;

/**
 * Container for a result returned by ipstack.com.
 */
class IpInfoResult implements GeocodeResult
{
    private ?array $data;
    private bool $fromCache = false;
    protected ?float $latitude;
    protected ?float $longitude;

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
        return $this->data['region'] ?? null;
    }

    /**
     * The administrative area (state, province, etc) as a code, if available.
     */
    public function getAdministrativeAreaCode(): ?string
    {
        return null;
    }

    /**
     * The name of the country
     */
    public function getCountry(): ?string
    {
        return $this->data['country'] ?? null;
    }

    /**
     * @inheritDoc
     */
    public function getCountryCode(): ?string
    {
        return $this->data['country'] ?? null;
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
        if (!isset($this->latitude)) {
            $this->parseLoc();
        }
        return $this->latitude;
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
        if (!isset($this->longitude)) {
            $this->parseLoc();
        }
        return $this->longitude;
    }

    /**
     * @inheritDoc
     */
    public function getPostalCode(): ?string
    {
        return $this->data['postal'] ?? null;
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
        return $this->data['timezone'] ?? null;
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

}
