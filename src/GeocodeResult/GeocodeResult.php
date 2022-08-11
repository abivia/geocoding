<?php

namespace Abivia\Geocode\GeocodeResult;

use Abivia\Cogs\AddressProperties;

interface GeocodeResult extends AddressProperties
{
    public function cached(?bool $set = null): bool;

    public function ipAddress(): string;

    public function latitude(): ?float;

    public function longitude(): ?float;

    public function timezone(): ?string;

}
