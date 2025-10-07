# Change log

### 3.0.1
Fixed:
* ipapi.co returns an error with a reason but no message when the IP address is a bogon,
this caused a warning. Now a LookupFailedException is thrown.

Added:
* Bogon tests to the live test suite.

### 3.0.0

Fixed:
* A deprecation warning with PHP 8.4.

Added:
* ipapi.co as a geo-location provider.
* If there is an error with the provider (bad key, invalid response), a `LookupFailedException`
is now thrown.

### 2.6.0

Added:
* `GeoCoder::getAddressFromHttp()` and `GeoCoder::lookupHttp()` now have an optional
  `$allowForward` parameter that can be used to ignore a `HTTP_X_FORWARDED_FOR` header.
  The parameter defaults true, but can be set false to ignore a correctly formatted,
  but obviously bogus address.

### 2.5.1

Fixed:
* To circumvent hacker abuse, `GeoCoder::getAddressFromHttp()`
  now falls back to using the server's `REMOTE_ADDR` when a value for `HTTP_X_FORWARDED_FOR`
  is provided but is not a valid address.

### 2.5.0

Added:
* `Geocoder::getSubnetAddress()` method by extracting code from `AbstractCache::subnetAddress()`.

### 2.4.1

Fixed:
* Failed to handle a null result when encoding an IP address in `Geocoder::getAddressFromHttp`.
  Now an AddressNotFoundException is thrown.
  The passed address is HTML escaped sor security.

### 2.4.0

Added:
* The `PdoCache` constructor now accepts a `dbOptions` array to allow passing of additional
  driver-specific attributes when creating tables. 

### 2.3.0

Added:
* `PdoCache` cache purge logic and options table.
  Changed constructor for `CacheHandler\PdoCache` to allow overriding of the options table name. 
  Last purge time is stored in the database
  and cached in a session variable prefixed by the classname.

### 2.2.0

Added:
* PdoCache handler, tests for PDO cache on a sqlite database.
* Abstract Cache class

Changed:
* Restructured cache handlers around the new abstract class.

### 2.1.0

Changed:
* Made the static function GeoCoder::getAddressFromHttp() public.

### 2.0.0

Added:
* Live query test for ipstack.com

Changed:
* Updated getter methods for compatibility with abivia/cogs 2.0

### 1.0.1 2022-08-11

Fixed:
* Error in ipinfo.io URL.

Added:
* Live query test for ipinfo.io
* Documentation for subnet queries
* MIT license text

### 1.0.0 Initial release.


