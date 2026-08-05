<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

require_once 'FakeCacheableService.php';

class AbstractServiceTest extends TestCase
{
    private function record(string $ip): array
    {
        return [
            'ip' => $ip,
            'city' => 'Testerville',
            'country_name' => 'Canada',
            'country_code' => 'CA',
            'is_crawler' => false,
            'is_tor' => false,
        ];
    }

    public function testQueryWithoutCacheAlwaysCallsQueryCore()
    {
        $service = new FakeCacheableService();
        $service->data['1.2.3.4'] = $this->record('1.2.3.4');

        $service->query('1.2.3.4');
        $service->query('1.2.3.4');

        $this->assertSame(2, $service->queryCoreCalls);
    }

    public function testQueryCachesResultOnSecondLookup()
    {
        $service = new FakeCacheableService();
        $service->setCache(new ArrayAdapter());
        $service->data['1.2.3.4'] = $this->record('1.2.3.4');

        $first = $service->query('1.2.3.4');
        $second = $service->query('1.2.3.4');

        $this->assertSame(1, $service->queryCoreCalls);
        $this->assertFalse($first->cached());
        $this->assertTrue($second->cached());
        $this->assertSame('1.2.3.4', $second->getIpAddress());
    }

    public function testQueryCachesEachAddressSeparately()
    {
        $service = new FakeCacheableService();
        $service->setCache(new ArrayAdapter());
        $service->data['1.2.3.4'] = $this->record('1.2.3.4');
        $service->data['5.6.7.8'] = $this->record('5.6.7.8');

        $service->query('1.2.3.4');
        $service->query('5.6.7.8');
        $service->query('1.2.3.4');

        $this->assertSame(2, $service->queryCoreCalls);
    }

    public function testQueryCachesANotFoundResult()
    {
        $service = new FakeCacheableService();
        $service->setCache(new ArrayAdapter());

        $first = $service->query('9.9.9.9');
        $second = $service->query('9.9.9.9');

        $this->assertNull($first);
        $this->assertNull($second);
        $this->assertSame(1, $service->queryCoreCalls);
    }

    public function testQueryCachesAnIpv6Address()
    {
        $service = new FakeCacheableService();
        $service->setCache(new ArrayAdapter());
        $address = '2001:db8::1';
        $service->data[$address] = $this->record($address);

        $first = $service->query($address);
        $second = $service->query($address);

        $this->assertNotNull($first);
        $this->assertNotNull($second);
        $this->assertSame(1, $service->queryCoreCalls);
        $this->assertTrue($second->cached());
    }

    public function testDifferentProviderClassesDoNotShareACacheEntry()
    {
        $cache = new ArrayAdapter();

        $serviceA = new FakeCacheableService();
        $serviceA->setCache($cache);
        $serviceA->data['1.2.3.4'] = $this->record('1.2.3.4');

        $serviceB = new class extends FakeCacheableService {
        };
        $serviceB->setCache($cache);
        $serviceB->data['1.2.3.4'] = $this->record('1.2.3.4');

        $serviceA->query('1.2.3.4');
        $serviceB->query('1.2.3.4');

        $this->assertSame(1, $serviceA->queryCoreCalls);
        $this->assertSame(1, $serviceB->queryCoreCalls);
    }
}
