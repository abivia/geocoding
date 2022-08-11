<?php

namespace Abivia\Geocode\LookupService;

use Abivia\Geocode\GeocodeResult\GeocodeResult;

interface LookupService
{
    public function query(string $address): ?GeocodeResult;
}
