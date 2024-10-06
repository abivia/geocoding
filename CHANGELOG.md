# Change log

# 2.3.0

Added:
* PdoCache cache purge logic and options table.
  Changed constructor for CacheHandler\PdoCache to allow overriding of the options table name. 
  Last purge time is stored in the database
  and cached in a session variable prefixed by the classname.

# 2.2.0

Added:
* PdoCache handler, tests for PDO cache on a sqlite database.
* Abstract Cache class

Changed:
* Restructured cache handlers around the new abstract class.

# 2.1.0

Changed:
* Made the static function GeoCoder::getAddressFromHttp() public.

# 2.0.0

Added:
* Live query test for ipstack.com

Changed:
* Updated getter methods for compatibility with abivia/cogs 2.0

## 1.0.1 2022-08-11

Fixed:
* Error in ipinfo.io URL.

Added:
* Live query test for ipinfo.io
* Documentation for subnet queries
* MIT license text

# 1.0.0 Initial release.


