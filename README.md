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

* PHP 8.4 or higher
* ext-curl to perform API calls
* abivia/cogs for address support
* nesbot/carbon for date support
* mlocati/ip-lib for IP address support
* symfony/cache for caching

PHP does not need to be built with IPv6 support.

## Installation

Via composer:

```composer require abivia/geocoding```

## Configuration

Configuration is passed to the lookup service at object instantiation, 
either to the constructor or through the `make()` factory method. 
All services recognize these common values:

* `key` The authentication key or token required to access the service.
* `ttl` The time, in seconds, to cache a successful lookup result. 
If not provided, the default is 24 hours.
* `missTtl` The time, in seconds, to cache a failed lookup. The default is 6 hours.

In addition, the `IpInfoApi` service accepts an API fallback list, stored in `apiList`.
Possible elements in the list are `core`, `lite`, and `free`.
The default is `['lite', 'free']`
Geocoder will attempt to use each API in the order provided.
If a service returns a HTTP 429, indicating that a monthly limit has been hit,
the next API in the list will be used. If a cache is provided, 
the lookup failure will be recorded and that API will not be accessed for `newMonth`
seconds after midnight on the first of the month. The default for `newMonth` is 24 hours.
The delay is to prevent a race condition where a 429 is received at the beginning of a new month,
which would cause an undesirable immediate fall-over to the next service for another month.

## Caching

Geocoding 3.x uses the Symfony cache mechanism.
Pass the cache adapter to the lookup service via the `setCache()` method.
The same cache can be used with multiple services without collisions.
Each service adds its name to the cache along with the Ip address.
This replaces the custom mechanism in previous versions.

## Data normalization

The lookup results from different services are normalized across providers.
Applications can access both the normalized and raw data from a service through the
GeocodeResult::data() method.
Passing no argument or false will return data from the lookup service.
Passing true will retrieve the normalized array. If a data element is not present in the response,
the normalized result will contain null.

## Proxy headers

The static `getAddressFromHttp()` method now accepts an optional `$proxyHeaders`
parameter. If the parameter is null or not provided, and `allowHeaders` is true,
GeoCoder looks for `HTTP_CF_CONNECTING_IP`, `HTTP_CLIENT_IP`,
and `HTTP_X_FORWARDED_FOR` headers, 
in that order (more proxies may be added as they are discovered).
Each `GeoCoder` instance can maintain an independent proxy list.

By default, and address lookup via `Geocoder::lookupHttp()` checks for the `HTTP_X_FORWARDED_FOR`
header. However, other services such as CloudFlare use non-standard headers. A call to
`Geocoder::addKnownProxyHeaders()` adds all known proxy headers,
which are checked for an IP address in order until a valid address is found.

Other proxies can be added or removed by calling `Geocoder::addProxyHeader()` 
and `Geocoder::removeProxyHeader()`. Header names are converted to uppercase. Headers are checked 
in the reverse order that they are added, so that the most recently added header is checked first.

## Sample Usage

```php
use Abivia\Geocode\Geocoder;
use Abivia\Geocode\LookupService\IpInfoApi;

$geocoder = new Geocoder(IpInfoApi::make(['key' => 'my_token']));
$info = $geocoder->lookup('4.4.4.4');
echo $info->getLatitude() . ', ' . $info->getLongitude();
```

## Subnet queries

From a geolocation perspective, often only the first 24 bits of an IPv4
and the first 48 of an IPv6 address are significant.
When caching is enabled, Geocoding provides a "subnet" query that will return the first
cached result in the same subnet.
This can reduce the number of queries on the lookup service,
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

# Use of Generative AI

The functional code was developed by hand. Most test cases were generated with the help of AI.
When running the first batch of tests, 
the algorithm detected an error and injected a one statement fix.
The fix was incomplete, suboptimal, and based on a partial understanding of the issue.
It has been removed and corrected.
From there, all changes to the code to address failing tests (and some updates to the tests)
were done without the assistance of AI beyond something that suggested line completion
(with a 45% error rate, hardly useful.)

# Donations welcome

If you're getting something out of Geocoding, you can sponsor us in any amount you wish using Liberapay
[![Liberapay](https://liberapay.com/assets/widgets/donate.svg)](https://liberapay.com/abivia/donate).
Liberapay is itself run on donations and takes no fees beyond bank charges.
