# Abivia Geocoding

The Abivia geocoding library provides a caching interface to IP address lookup APIs provided by
ipinfo.io and ipstack.com. The library supports both IPv4 and IPv6 addresses and is designed to
seamlessly support other IP address geocoding services.

## Requirements

Geocoding requires:

* PHP 8.1 or higher
* ext-curl to perform API calls
* abivia/cogs for address support
* mlocati/ip-lib for IP address support

PHP does not need to be built with IPv6 support.

## Installation

Via composer:

```composer require abivia\geocoding```

## Caching

Geocoding comes with two cache handlers designed for light use. The array cache will retain lookup
results for the lifetime of the current request (or session if stored in a session variable). The
file cache stores cached data in a text file. Neither offers support for concurrent requests.

Applications can create persistent caches by conforming to the CacheHandler interface. Caches
support different cache times for lookup hits and lookup misses. In a file cache, the default cache
time for a hit is seven days, the cache time for a miss is one hour. 

## Sample Usage

```php
use Abivia\Geocode\Geocoder;
use Abivia\Geocode\LookupService\IpInfoApi;

$geocoder = new Geocoder(IpInfoApi::make());
$info = $geocoder->lookup('4.4.4.4');
echo $info->latitude() . ', ' . $info->longitude();
```

The geocoder normalizes results from different services using the GeocodeResult interface, but 
applications can retrieve the service's response through the GeocodeResult::data() method to access
extended information.

## Using a file cache

```php
use Abivia\Geocode\CacheHandler\FileCache;
use Abivia\Geocode\Geocoder;
use Abivia\Geocode\LookupService\IpInfoApi;

// Get a cache and set the cache time for a hit to six hours
$cache = new FileCache('mycache.json');
$cache->hitCacheTime(6 * 3600);
$geocoder = new Geocoder(IpInfoApi::make(), $cache);
$info = $geocoder->lookup('4.4.4.4');
echo $info->latitude() . ', ' . $info->longitude();
```

## Subnet queries

For the most part from a geolocation perspective, only the first 24 bits of an IPv4 and the first
48 of an IPv6 address are significant. Geocoding provides a "subnet" query that will return the last
queries result in the v4 or v6 range. This can reduce the number of queries on the lookup service,
increasing performance and (in the case of paid services) reducing costs.

### Subnet Example

```php
use Abivia\Geocode\Geocoder;
use Abivia\Geocode\LookupService\IpInfoApi;

$geocoder = new Geocoder(IpInfoApi::make());

// Assume 4.4.4.4 is not currently cached. This will cause the service to be queried. 
$info = $geocoder->lookupSubnet('4.4.4.4');
echo $info->latitude() . ', ' . $info->longitude();

// This query will return the cached data for 4.4.4.4
$info2 = $geocoder->lookupSubnet('4.4.4.8');
```
