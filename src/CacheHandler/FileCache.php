<?php

namespace Abivia\Geocode\CacheHandler;

use function time;

/**
 * The file cache is designed for medium term caching when the number of cached items is
 * relatively low. Initial cache load time will increase with the number of items cached.
 */
class FileCache extends ArrayCache implements CacheHandler
{

    protected string $cacheFile;

    public function __construct(string $cacheFile)
    {
        $this->hitCacheTime = 7 * 24 * 3600;
        $this->cacheFile = $cacheFile;
        $this->loadCache();
    }

    public function __destruct()
    {
        $this->saveCache();
    }

    private function loadCache()
    {
        if (file_exists($this->cacheFile)) {
            $cacheInfo = json_decode(file_get_contents($this->cacheFile));
            if ($cacheInfo) {
                $this->cache = unserialize($cacheInfo->cache);
                $this->subnets = unserialize($cacheInfo->Subnets);
                $this->hitCacheTime = $cacheInfo->hitCacheTime;
                $this->missCacheTime = $cacheInfo->missCacheTime;
            }
            $this->purgeExpiredCache();
        } else {
            $this->cache = [];
            $this->subnets = [];
        }
    }

    private function purgeExpiredCache()
    {
        $now = time();
        foreach ($this->cache as $key => $entry) {
            if ($now > $entry['expires']) {
                unset($this->cache[$key]);
            }
        }
        foreach ($this->subnets as $Subnet => $reference) {
            if (!isset($this->cache[$reference])) {
                unset($this->subnets[$Subnet]);
            }
        }
    }

    private function saveCache()
    {
        $cacheInfo = [
            'cache' => serialize($this->cache),
            'Subnets'=>serialize($this->subnets),
            'hitCacheTime' => $this->hitCacheTime,
            'missCacheTime' => $this->missCacheTime,
        ];
        file_put_contents($this->cacheFile, json_encode($cacheInfo));
    }

}
