<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class ServerControllerTest extends WebTestCase
{
    public function testGetServersReturnsJsonResponse(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/servers');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/json');
    }

    public function testGetServersReturnsExpectedStructure(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/servers');

        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('data', $response);
        $this->assertArrayHasKey('meta', $response);
        $this->assertArrayHasKey('filters', $response);

        // Check meta structure
        $this->assertArrayHasKey('total', $response['meta']);
        $this->assertArrayHasKey('page', $response['meta']);
        $this->assertArrayHasKey('limit', $response['meta']);
        $this->assertArrayHasKey('totalPages', $response['meta']);
        $this->assertArrayHasKey('hasNextPage', $response['meta']);
        $this->assertArrayHasKey('hasPrevPage', $response['meta']);
    }

    public function testGetServersReturnsPaginatedResults(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/servers?limit=5');

        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertCount(5, $response['data']);
        $this->assertEquals(5, $response['meta']['limit']);
        $this->assertEquals(1, $response['meta']['page']);
    }

    public function testGetServersSecondPage(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/servers?page=2&limit=10');

        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals(2, $response['meta']['page']);
        $this->assertTrue($response['meta']['hasPrevPage']);
    }

    public function testGetServersWithRamFilter(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/servers?ram[]=64GB');

        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertNotEmpty($response['data']);
        foreach ($response['data'] as $server) {
            $this->assertEquals(64, $server['ramSizeGb']);
        }
    }

    public function testGetServersWithHddTypeFilter(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/servers?hddType=SSD');

        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertNotEmpty($response['data']);
        foreach ($response['data'] as $server) {
            $this->assertEquals('SSD', $server['hddType']);
        }
    }

    public function testGetServersWithLocationFilter(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/servers?location=AmsterdamAMS-01');

        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertNotEmpty($response['data']);
        foreach ($response['data'] as $server) {
            $this->assertEquals('AmsterdamAMS-01', $server['location']);
        }
    }

    public function testGetServersWithStorageFilter(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/servers?storage[]=0-250GB');

        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertNotEmpty($response['data']);
        foreach ($response['data'] as $server) {
            $this->assertLessThan(250, $server['storageTotalGb']);
        }
    }

    public function testGetServersWithMultipleFilters(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/servers?ram[]=64GB&hddType=SSD');

        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertNotEmpty($response['data']);
        foreach ($response['data'] as $server) {
            $this->assertEquals(64, $server['ramSizeGb']);
            $this->assertEquals('SSD', $server['hddType']);
        }
    }

    public function testGetServersReturnsServerProperties(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/servers?limit=1');

        $response = json_decode($client->getResponse()->getContent(), true);
        $server = $response['data'][0];

        $this->assertArrayHasKey('id', $server);
        $this->assertArrayHasKey('model', $server);
        $this->assertArrayHasKey('ram', $server);
        $this->assertArrayHasKey('ramSizeGb', $server);
        $this->assertArrayHasKey('hdd', $server);
        $this->assertArrayHasKey('storageTotalGb', $server);
        $this->assertArrayHasKey('hddType', $server);
        $this->assertArrayHasKey('location', $server);
        $this->assertArrayHasKey('price', $server);
        $this->assertArrayHasKey('currency', $server);
        $this->assertArrayHasKey('formattedPrice', $server);
    }

    public function testGetSingleServerReturnsCorrectData(): void
    {
        $client = static::createClient();
        
        // First get a server ID
        $client->request('GET', '/api/servers?limit=1');
        $listResponse = json_decode($client->getResponse()->getContent(), true);
        $serverId = $listResponse['data'][0]['id'];

        // Then fetch single server
        $client->request('GET', '/api/servers/' . $serverId);

        $this->assertResponseIsSuccessful();
        
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('data', $response);
        $this->assertEquals($serverId, $response['data']['id']);
    }

    public function testGetSingleServerNotFound(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/servers/999999');

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $response);
    }

    public function testGetFiltersReturnsExpectedStructure(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/filters');

        $this->assertResponseIsSuccessful();
        
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('data', $response);
        $this->assertArrayHasKey('storage', $response['data']);
        $this->assertArrayHasKey('ram', $response['data']);
        $this->assertArrayHasKey('hddType', $response['data']);
        $this->assertArrayHasKey('location', $response['data']);
    }

    public function testGetFiltersStorageOptions(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/filters');

        $response = json_decode($client->getResponse()->getContent(), true);
        $storageOptions = $response['data']['storage'];

        $this->assertNotEmpty($storageOptions);
        
        $firstOption = $storageOptions[0];
        $this->assertArrayHasKey('value', $firstOption);
        $this->assertArrayHasKey('label', $firstOption);
        $this->assertArrayHasKey('min', $firstOption);
        $this->assertArrayHasKey('max', $firstOption);
    }

    public function testGetFiltersRamOptions(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/filters');

        $response = json_decode($client->getResponse()->getContent(), true);
        $ramOptions = $response['data']['ram'];

        $this->assertNotEmpty($ramOptions);
        
        $firstOption = $ramOptions[0];
        $this->assertArrayHasKey('value', $firstOption);
        $this->assertArrayHasKey('label', $firstOption);
        $this->assertArrayHasKey('sizeGb', $firstOption);
    }

    public function testGetFiltersHddTypes(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/filters');

        $response = json_decode($client->getResponse()->getContent(), true);
        $hddTypes = $response['data']['hddType'];

        $this->assertContains('SAS', $hddTypes);
        $this->assertContains('SATA', $hddTypes);
        $this->assertContains('SSD', $hddTypes);
    }

    public function testGetFiltersLocations(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/filters');

        $response = json_decode($client->getResponse()->getContent(), true);
        $locations = $response['data']['location'];

        $this->assertNotEmpty($locations);
        $this->assertContains('AmsterdamAMS-01', $locations);
    }

    public function testGetServersWithSortByPrice(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/servers?sort=price&order=asc&limit=5');

        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertNotEmpty($response['data']);
        $this->assertEquals('price', $response['meta']['sort']);
        $this->assertEquals('asc', $response['meta']['order']);

        // Verify ascending order
        $prices = array_map(fn($s) => (float) $s['price'], $response['data']);
        $sortedPrices = $prices;
        sort($sortedPrices);
        $this->assertEquals($sortedPrices, $prices);
    }

    public function testGetServersWithSortByPriceDesc(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/servers?sort=price&order=desc&limit=5');

        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertNotEmpty($response['data']);
        $this->assertEquals('price', $response['meta']['sort']);
        $this->assertEquals('desc', $response['meta']['order']);

        // Verify descending order
        $prices = array_map(fn($s) => (float) $s['price'], $response['data']);
        $sortedPrices = $prices;
        rsort($sortedPrices);
        $this->assertEquals($sortedPrices, $prices);
    }

    public function testGetServersWithSortByRam(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/servers?sort=ram&order=asc&limit=10');

        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertNotEmpty($response['data']);
        $this->assertEquals('ram', $response['meta']['sort']);

        // Verify ascending order by RAM
        $ramValues = array_map(fn($s) => $s['ramSizeGb'], $response['data']);
        $sortedRam = $ramValues;
        sort($sortedRam);
        $this->assertEquals($sortedRam, $ramValues);
    }

    public function testGetServersWithSortByStorage(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/servers?sort=storage&order=desc&limit=10');

        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertNotEmpty($response['data']);
        $this->assertEquals('storage', $response['meta']['sort']);

        // Verify descending order by storage
        $storageValues = array_map(fn($s) => $s['storageTotalGb'], $response['data']);
        $sortedStorage = $storageValues;
        rsort($sortedStorage);
        $this->assertEquals($sortedStorage, $storageValues);
    }

    public function testGetServersWithSortByModel(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/servers?sort=model&order=asc&limit=5');

        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertNotEmpty($response['data']);
        $this->assertEquals('model', $response['meta']['sort']);
    }

    public function testGetServersDefaultSorting(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/servers?limit=5');

        $response = json_decode($client->getResponse()->getContent(), true);

        // Default should be price ascending
        $this->assertEquals('price', $response['meta']['sort']);
        $this->assertEquals('asc', $response['meta']['order']);
    }

    public function testGetServersWithPriceRangeFilter(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/servers?priceMin=50&priceMax=100');

        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertNotEmpty($response['data']);
        foreach ($response['data'] as $server) {
            $price = (float) $server['price'];
            $this->assertGreaterThanOrEqual(50, $price);
            $this->assertLessThanOrEqual(100, $price);
        }
    }

    public function testGetServersWithPriceMinOnly(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/servers?priceMin=200');

        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertNotEmpty($response['data']);
        foreach ($response['data'] as $server) {
            $price = (float) $server['price'];
            $this->assertGreaterThanOrEqual(200, $price);
        }
    }

    public function testGetServersWithPriceMaxOnly(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/servers?priceMax=60');

        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertNotEmpty($response['data']);
        foreach ($response['data'] as $server) {
            $price = (float) $server['price'];
            $this->assertLessThanOrEqual(60, $price);
        }
    }

    public function testGetServersWithPriceFilterAndSorting(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/servers?priceMin=50&priceMax=150&sort=ram&order=desc');

        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertNotEmpty($response['data']);
        
        // Verify price range
        foreach ($response['data'] as $server) {
            $price = (float) $server['price'];
            $this->assertGreaterThanOrEqual(50, $price);
            $this->assertLessThanOrEqual(150, $price);
        }

        // Verify sorting
        $this->assertEquals('ram', $response['meta']['sort']);
        $this->assertEquals('desc', $response['meta']['order']);
    }

    public function testGetServersMetaIncludesSortInfo(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/servers');

        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('sort', $response['meta']);
        $this->assertArrayHasKey('order', $response['meta']);
    }
}

