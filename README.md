# Abivia Geocoding

The Abivia geocoding library provides a caching interface to IP address lookup APIs
from these sources:

- ipinfo.io 
- ipstack.com
- ipapi.co

The library supports both IPv4 and IPv6 addresses and is designed to
seamlessly support other IP address geocoding services.

## Requirements

Geocoding requires:

* PHP 8.2 or higher
* ext-curl to perform API calls
* abivia/cogs for address support
* mlocati/ip-lib for IP address support

PHP does not need to be built with IPv6 support.

## Installation

Via composer:

```composer require abivia/geocoding```

## Caching

Geocoding comes with two cache handlers designed for light use and a database cache for heavier use.
The array cache will retain lookup results for the lifetime of the current request (or session if
stored in a session variable).
The file cache stores cached data in a text file.
Neither offers support for concurrent requests.

The PDO Cache takes a connection to a database, optionally with table names to override the
default tables `geocoder_cache_ip`, `geocoder_cache_subnet` and `geocoder_cache_options`.
The cache handler will create these tables if they do not already exist.

Applications can create persistent caches by conforming to the CacheHandler interface. Caches
support different retention times for lookup hits and lookup misses.
In a file cache, the default retention time for a successful lookup is seven days, 
the default retention time for a failed lookup is one hour.
The PDO cache defaults to 30 days for a successful lookup and one hour for a failed lookup.

### Cache Purging

Array caches are expected to be transient, so there is no purge logic.
The file cache runs a purge at the time the cache is loaded.
The PDO cache runs at the interval set by the `purgeCacheTime()` method, which defaults to 24 hours.
On the first object creation, 
the last purge time is loaded from the database and cached in the session to reduce overhead.

## Sample Usage

```php
use Abivia\Geocode\Geocoder;
use Abivia\Geocode\LookupService\IpInfoApi;

$geocoder = new Geocoder(IpInfoApi::make());
$info = $geocoder->lookup('4.4.4.4');
echo $info->getLatitude() . ', ' . $info->getLongitude();
```

The geocoder normalizes results from different services using the GeocodeResult interface, but 
applications can retrieve the service's response through the GeocodeResult::data() method to access
extended information.

## Example using a file cache

```php
use Abivia\Geocode\CacheHandler\FileCache;
use Abivia\Geocode\Geocoder;
use Abivia\Geocode\LookupService\IpInfoApi;

// Get a cache and set the cache time for a hit to six hours
$cache = new FileCache('mycache.json');
$cache->hitCacheTime(6 * 3600);
$geocoder = new Geocoder(IpInfoApi::make(), $cache);
$info = $geocoder->lookup('4.4.4.4');
echo $info->getLatitude() . ', ' . $info->getLongitude();
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
echo $info->getLatitude() . ', ' . $info->getLongitude();

// This query will return the cached data for 4.4.4.4
$info2 = $geocoder->lookupSubnet('4.4.4.8');
```

## Donations welcome

If you're getting something out of Geocoding, you can sponsor us in any amount you wish using Liberapay
[![Liberapay](https://liberapay.com/assets/widgets/donate.svg)](https://liberapay.com/abivia/donate).
Liberapay is itself run on donations and takes no fees beyond bank charges.
