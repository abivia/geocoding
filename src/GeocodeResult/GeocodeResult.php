<?php

namespace Abivia\Geocode\GeocodeResult;

use Abivia\Cogs\AddressProperties;

interface GeocodeResult extends AddressProperties
{
    public function cached(?bool $set = null): bool;

    public function getIpAddress(): string;

    public function getLatitude(): ?float;

    public function getLongitude(): ?float;

    public function getTimezone(): ?string;

}
