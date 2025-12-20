<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\ServerRepository;
use Symfony\Component\HttpFoundation\Request;

class ServerFilterService
{
    // Storage ranges as defined in the Excel filter specification
    public const STORAGE_RANGES = [
        '0-250GB' => ['min' => 0, 'max' => 250, 'label' => '0 - 250 GB'],
        '250GB-500GB' => ['min' => 250, 'max' => 500, 'label' => '250 - 500 GB'],
        '500GB-1TB' => ['min' => 500, 'max' => 1000, 'label' => '500 GB - 1 TB'],
        '1TB-2TB' => ['min' => 1000, 'max' => 2000, 'label' => '1 - 2 TB'],
        '2TB-3TB' => ['min' => 2000, 'max' => 3000, 'label' => '2 - 3 TB'],
        '3TB-4TB' => ['min' => 3000, 'max' => 4000, 'label' => '3 - 4 TB'],
        '4TB-8TB' => ['min' => 4000, 'max' => 8000, 'label' => '4 - 8 TB'],
        '8TB-12TB' => ['min' => 8000, 'max' => 12000, 'label' => '8 - 12 TB'],
        '12TB-24TB' => ['min' => 12000, 'max' => 24000, 'label' => '12 - 24 TB'],
        '24TB-48TB' => ['min' => 24000, 'max' => 48000, 'label' => '24 - 48 TB'],
        '48TB-72TB' => ['min' => 48000, 'max' => 72000, 'label' => '48 - 72 TB'],
        '72TB+' => ['min' => 72000, 'max' => null, 'label' => '72 TB+'],
    ];

    // RAM values as defined in the Excel filter specification
    public const RAM_OPTIONS = [2, 4, 8, 12, 16, 24, 32, 48, 64, 96, 128];

    // HDD types
    public const HDD_TYPES = ['SAS', 'SATA', 'SSD'];

    // Valid sort fields
    public const SORT_FIELDS = ['price', 'ram', 'storage', 'model'];

    // Valid sort orders
    public const SORT_ORDERS = ['asc', 'desc'];

    public function __construct(
        private readonly ServerRepository $serverRepository
    ) {
    }

    /**
     * Extract and validate filters from request
     *
     * @return array<string, mixed>
     */
    public function extractFilters(Request $request): array
    {
        $filters = [];

        // Storage filter (array of ranges)
        $storage = $request->query->all('storage');
        if (!empty($storage) && is_array($storage)) {
            $filters['storage'] = $this->validateStorageRanges($storage);
        }

        // RAM filter (array of values)
        $ram = $request->query->all('ram');
        if (!empty($ram) && is_array($ram)) {
            $filters['ram'] = $this->validateRamValues($ram);
        }

        // HDD type filter (single value)
        $hddType = $request->query->get('hddType');
        if (!empty($hddType) && in_array(strtoupper($hddType), self::HDD_TYPES, true)) {
            $filters['hddType'] = strtoupper($hddType);
        }

        // Location filter (single value)
        $location = $request->query->get('location');
        if (!empty($location)) {
            $filters['location'] = $location;
        }

        // Price range filter
        $priceMin = $request->query->get('priceMin');
        if ($priceMin !== null && is_numeric($priceMin) && (float) $priceMin >= 0) {
            $filters['priceMin'] = (float) $priceMin;
        }

        $priceMax = $request->query->get('priceMax');
        if ($priceMax !== null && is_numeric($priceMax) && (float) $priceMax >= 0) {
            $filters['priceMax'] = (float) $priceMax;
        }

        return $filters;
    }

    /**
     * Validate storage range values
     *
     * @param array<int, string> $ranges
     * @return array<int, string>
     */
    private function validateStorageRanges(array $ranges): array
    {
        $validRanges = [];
        $rangeKeys = array_keys(self::STORAGE_RANGES);

        foreach ($ranges as $range) {
            $range = strtoupper(str_replace(' ', '', $range));
            if (in_array($range, $rangeKeys, true)) {
                $validRanges[] = $range;
            }
        }

        return $validRanges;
    }

    /**
     * Validate RAM values
     *
     * @param array<int, string> $values
     * @return array<int, string>
     */
    private function validateRamValues(array $values): array
    {
        $validValues = [];

        foreach ($values as $value) {
            $numValue = (int) preg_replace('/[^0-9]/', '', $value);
            if (in_array($numValue, self::RAM_OPTIONS, true)) {
                $validValues[] = $numValue . 'GB';
            }
        }

        return $validValues;
    }

    /**
     * Get pagination parameters from request
     *
     * @return array{page: int, limit: int}
     */
    public function extractPagination(Request $request): array
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = min(100, max(1, (int) $request->query->get('limit', 20)));

        return [
            'page' => $page,
            'limit' => $limit,
        ];
    }

    /**
     * Get sorting parameters from request
     *
     * @return array{sort: string, order: string}
     */
    public function extractSorting(Request $request): array
    {
        $sort = strtolower($request->query->get('sort', 'price'));
        $order = strtolower($request->query->get('order', 'asc'));

        // Validate sort field
        if (!in_array($sort, self::SORT_FIELDS, true)) {
            $sort = 'price';
        }

        // Validate sort order
        if (!in_array($order, self::SORT_ORDERS, true)) {
            $order = 'asc';
        }

        return [
            'sort' => $sort,
            'order' => $order,
        ];
    }

    /**
     * Get all available filter options
     *
     * @return array<string, mixed>
     */
    public function getAvailableFilters(): array
    {
        return [
            'storage' => $this->getStorageOptions(),
            'ram' => $this->getRamOptions(),
            'hddType' => self::HDD_TYPES,
            'location' => $this->serverRepository->getDistinctLocations(),
        ];
    }

    /**
     * Get storage range options
     *
     * @return array<int, array{value: string, label: string, min: int, max: int|null}>
     */
    private function getStorageOptions(): array
    {
        $options = [];
        foreach (self::STORAGE_RANGES as $value => $config) {
            $options[] = [
                'value' => $value,
                'label' => $config['label'],
                'min' => $config['min'],
                'max' => $config['max'],
            ];
        }
        return $options;
    }

    /**
     * Get RAM options
     *
     * @return array<int, array{value: string, label: string, sizeGb: int}>
     */
    private function getRamOptions(): array
    {
        return array_map(fn($size) => [
            'value' => $size . 'GB',
            'label' => $size . ' GB',
            'sizeGb' => $size,
        ], self::RAM_OPTIONS);
    }
}

