<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Repository\ServerRepository;
use App\Service\ServerFilterService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class ServerFilterServiceTest extends TestCase
{
    private ServerFilterService $filterService;
    private ServerRepository&MockObject $serverRepository;

    protected function setUp(): void
    {
        $this->serverRepository = $this->createMock(ServerRepository::class);
        $this->filterService = new ServerFilterService($this->serverRepository);
    }

    public function testExtractFiltersWithEmptyRequest(): void
    {
        $request = new Request();
        $filters = $this->filterService->extractFilters($request);

        $this->assertEmpty($filters);
    }

    public function testExtractFiltersWithValidStorageRanges(): void
    {
        $request = new Request([
            'storage' => ['0-250GB', '500GB-1TB'],
        ]);

        $filters = $this->filterService->extractFilters($request);

        $this->assertArrayHasKey('storage', $filters);
        $this->assertContains('0-250GB', $filters['storage']);
        $this->assertContains('500GB-1TB', $filters['storage']);
    }

    public function testExtractFiltersIgnoresInvalidStorageRanges(): void
    {
        $request = new Request([
            'storage' => ['0-250GB', 'invalid-range', '500GB-1TB'],
        ]);

        $filters = $this->filterService->extractFilters($request);

        $this->assertArrayHasKey('storage', $filters);
        $this->assertCount(2, $filters['storage']);
        $this->assertNotContains('invalid-range', $filters['storage']);
    }

    public function testExtractFiltersWithValidRamValues(): void
    {
        $request = new Request([
            'ram' => ['16GB', '32GB', '64GB'],
        ]);

        $filters = $this->filterService->extractFilters($request);

        $this->assertArrayHasKey('ram', $filters);
        $this->assertContains('16GB', $filters['ram']);
        $this->assertContains('32GB', $filters['ram']);
        $this->assertContains('64GB', $filters['ram']);
    }

    public function testExtractFiltersIgnoresInvalidRamValues(): void
    {
        $request = new Request([
            'ram' => ['16GB', '999GB', '32GB'],
        ]);

        $filters = $this->filterService->extractFilters($request);

        $this->assertArrayHasKey('ram', $filters);
        $this->assertCount(2, $filters['ram']);
        $this->assertNotContains('999GB', $filters['ram']);
    }

    public function testExtractFiltersWithValidHddType(): void
    {
        $request = new Request([
            'hddType' => 'SSD',
        ]);

        $filters = $this->filterService->extractFilters($request);

        $this->assertArrayHasKey('hddType', $filters);
        $this->assertEquals('SSD', $filters['hddType']);
    }

    public function testExtractFiltersWithLowercaseHddType(): void
    {
        $request = new Request([
            'hddType' => 'sata',
        ]);

        $filters = $this->filterService->extractFilters($request);

        $this->assertArrayHasKey('hddType', $filters);
        $this->assertEquals('SATA', $filters['hddType']);
    }

    public function testExtractFiltersIgnoresInvalidHddType(): void
    {
        $request = new Request([
            'hddType' => 'INVALID',
        ]);

        $filters = $this->filterService->extractFilters($request);

        $this->assertArrayNotHasKey('hddType', $filters);
    }

    public function testExtractFiltersWithLocation(): void
    {
        $request = new Request([
            'location' => 'AmsterdamAMS-01',
        ]);

        $filters = $this->filterService->extractFilters($request);

        $this->assertArrayHasKey('location', $filters);
        $this->assertEquals('AmsterdamAMS-01', $filters['location']);
    }

    public function testExtractPaginationDefaults(): void
    {
        $request = new Request();
        $pagination = $this->filterService->extractPagination($request);

        $this->assertEquals(1, $pagination['page']);
        $this->assertEquals(20, $pagination['limit']);
    }

    public function testExtractPaginationWithCustomValues(): void
    {
        $request = new Request([
            'page' => '3',
            'limit' => '50',
        ]);

        $pagination = $this->filterService->extractPagination($request);

        $this->assertEquals(3, $pagination['page']);
        $this->assertEquals(50, $pagination['limit']);
    }

    public function testExtractPaginationEnforcesMinimums(): void
    {
        $request = new Request([
            'page' => '-1',
            'limit' => '0',
        ]);

        $pagination = $this->filterService->extractPagination($request);

        $this->assertEquals(1, $pagination['page']);
        $this->assertEquals(1, $pagination['limit']);
    }

    public function testExtractPaginationEnforcesMaxLimit(): void
    {
        $request = new Request([
            'limit' => '500',
        ]);

        $pagination = $this->filterService->extractPagination($request);

        $this->assertEquals(100, $pagination['limit']);
    }

    public function testGetAvailableFiltersReturnsAllOptions(): void
    {
        $this->serverRepository->method('getDistinctLocations')
            ->willReturn(['AmsterdamAMS-01', 'FrankfurtFRA-10']);

        $filters = $this->filterService->getAvailableFilters();

        $this->assertArrayHasKey('storage', $filters);
        $this->assertArrayHasKey('ram', $filters);
        $this->assertArrayHasKey('hddType', $filters);
        $this->assertArrayHasKey('location', $filters);

        $this->assertCount(12, $filters['storage']); // 12 storage ranges
        $this->assertCount(11, $filters['ram']); // 11 RAM options
        $this->assertCount(3, $filters['hddType']); // SAS, SATA, SSD
        $this->assertContains('AmsterdamAMS-01', $filters['location']);
    }

    public function testStorageRangeConstants(): void
    {
        $this->assertArrayHasKey('0-250GB', ServerFilterService::STORAGE_RANGES);
        $this->assertArrayHasKey('72TB+', ServerFilterService::STORAGE_RANGES);

        $range = ServerFilterService::STORAGE_RANGES['0-250GB'];
        $this->assertEquals(0, $range['min']);
        $this->assertEquals(250, $range['max']);
    }

    public function testRamOptionsConstants(): void
    {
        $this->assertContains(2, ServerFilterService::RAM_OPTIONS);
        $this->assertContains(128, ServerFilterService::RAM_OPTIONS);
        $this->assertNotContains(256, ServerFilterService::RAM_OPTIONS);
    }

    public function testHddTypesConstants(): void
    {
        $this->assertContains('SAS', ServerFilterService::HDD_TYPES);
        $this->assertContains('SATA', ServerFilterService::HDD_TYPES);
        $this->assertContains('SSD', ServerFilterService::HDD_TYPES);
        $this->assertCount(3, ServerFilterService::HDD_TYPES);
    }

    public function testExtractFiltersWithPriceRange(): void
    {
        $request = new Request([
            'priceMin' => '50',
            'priceMax' => '200',
        ]);

        $filters = $this->filterService->extractFilters($request);

        $this->assertArrayHasKey('priceMin', $filters);
        $this->assertArrayHasKey('priceMax', $filters);
        $this->assertEquals(50.0, $filters['priceMin']);
        $this->assertEquals(200.0, $filters['priceMax']);
    }

    public function testExtractFiltersWithPriceMinOnly(): void
    {
        $request = new Request([
            'priceMin' => '100.50',
        ]);

        $filters = $this->filterService->extractFilters($request);

        $this->assertArrayHasKey('priceMin', $filters);
        $this->assertArrayNotHasKey('priceMax', $filters);
        $this->assertEquals(100.50, $filters['priceMin']);
    }

    public function testExtractFiltersIgnoresInvalidPriceValues(): void
    {
        $request = new Request([
            'priceMin' => 'invalid',
            'priceMax' => '-50',
        ]);

        $filters = $this->filterService->extractFilters($request);

        $this->assertArrayNotHasKey('priceMin', $filters);
        $this->assertArrayNotHasKey('priceMax', $filters);
    }

    public function testExtractSortingDefaults(): void
    {
        $request = new Request();
        $sorting = $this->filterService->extractSorting($request);

        $this->assertEquals('price', $sorting['sort']);
        $this->assertEquals('asc', $sorting['order']);
    }

    public function testExtractSortingWithValidValues(): void
    {
        $request = new Request([
            'sort' => 'ram',
            'order' => 'desc',
        ]);

        $sorting = $this->filterService->extractSorting($request);

        $this->assertEquals('ram', $sorting['sort']);
        $this->assertEquals('desc', $sorting['order']);
    }

    public function testExtractSortingWithStorageField(): void
    {
        $request = new Request([
            'sort' => 'storage',
            'order' => 'asc',
        ]);

        $sorting = $this->filterService->extractSorting($request);

        $this->assertEquals('storage', $sorting['sort']);
        $this->assertEquals('asc', $sorting['order']);
    }

    public function testExtractSortingWithModelField(): void
    {
        $request = new Request([
            'sort' => 'model',
        ]);

        $sorting = $this->filterService->extractSorting($request);

        $this->assertEquals('model', $sorting['sort']);
    }

    public function testExtractSortingIgnoresInvalidSortField(): void
    {
        $request = new Request([
            'sort' => 'invalid_field',
        ]);

        $sorting = $this->filterService->extractSorting($request);

        $this->assertEquals('price', $sorting['sort']);
    }

    public function testExtractSortingIgnoresInvalidOrder(): void
    {
        $request = new Request([
            'order' => 'invalid_order',
        ]);

        $sorting = $this->filterService->extractSorting($request);

        $this->assertEquals('asc', $sorting['order']);
    }

    public function testSortFieldsConstants(): void
    {
        $this->assertContains('price', ServerFilterService::SORT_FIELDS);
        $this->assertContains('ram', ServerFilterService::SORT_FIELDS);
        $this->assertContains('storage', ServerFilterService::SORT_FIELDS);
        $this->assertContains('model', ServerFilterService::SORT_FIELDS);
        $this->assertCount(4, ServerFilterService::SORT_FIELDS);
    }

    public function testSortOrdersConstants(): void
    {
        $this->assertContains('asc', ServerFilterService::SORT_ORDERS);
        $this->assertContains('desc', ServerFilterService::SORT_ORDERS);
        $this->assertCount(2, ServerFilterService::SORT_ORDERS);
    }
}

