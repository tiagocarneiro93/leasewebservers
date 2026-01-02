<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Server;
use App\Service\ServerFilterService;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * @extends ServiceEntityRepository<Server>
 */
class ServerRepository extends ServiceEntityRepository
{
    /**
     * Cache TTL in seconds (1 hour).
     * Server data rarely changes, so we cache aggressively.
     */
    private const CACHE_TTL = 3600;

    /**
     * Cache key prefix for server queries.
     */
    private const CACHE_PREFIX = 'servers_';

    public function __construct(
        ManagerRegistry $registry,
        private readonly CacheItemPoolInterface $serverCache
    ) {
        parent::__construct($registry, Server::class);
    }

    /**
     * Find servers with filters, pagination, and sorting.
     * Results are cached for performance.
     *
     * @param array<string, mixed> $filters
     * @param array{sort: string, order: string} $sorting
     * @return array{servers: Server[], total: int, page: int, limit: int, totalPages: int}
     */
    public function findByFilters(
        array $filters,
        int $page = 1,
        int $limit = 20,
        array $sorting = ['sort' => 'price', 'order' => 'asc']
    ): array {
        $cacheKey = $this->generateCacheKey($filters, $page, $limit, $sorting);

        $cacheItem = $this->serverCache->getItem($cacheKey);

        if ($cacheItem->isHit()) {
            return $cacheItem->get();
        }

        // Execute query when cache miss
        $result = $this->executeFilterQuery($filters, $page, $limit, $sorting);

        // Cache the result
        $cacheItem->set($result);
        $cacheItem->expiresAfter(self::CACHE_TTL);
        $this->serverCache->save($cacheItem);

        return $result;
    }

    /**
     * Execute the actual database query (used on cache miss).
     *
     * @param array<string, mixed> $filters
     * @param array{sort: string, order: string} $sorting
     * @return array{servers: Server[], total: int, page: int, limit: int, totalPages: int}
     */
    private function executeFilterQuery(
        array $filters,
        int $page,
        int $limit,
        array $sorting
    ): array {
        $qb = $this->createQueryBuilder('s');

        $this->applyFilters($qb, $filters);
        $this->applySorting($qb, $sorting);

        // Pagination
        $qb->setFirstResult(($page - 1) * $limit)
           ->setMaxResults($limit);

        $paginator = new Paginator($qb, false);
        $total = count($paginator);
        $servers = iterator_to_array($paginator);

        return [
            'servers' => $servers,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'totalPages' => (int) ceil($total / $limit),
        ];
    }

    /**
     * Generate a unique cache key based on query parameters.
     *
     * @param array<string, mixed> $filters
     * @param array{sort: string, order: string} $sorting
     */
    private function generateCacheKey(array $filters, int $page, int $limit, array $sorting): string
    {
        // Sort arrays to ensure consistent key generation
        ksort($filters);

        $keyData = [
            'f' => $filters,
            'p' => $page,
            'l' => $limit,
            's' => $sorting,
        ];

        return self::CACHE_PREFIX . md5(serialize($keyData));
    }

    /**
     * Invalidate all server-related cache entries.
     * Call this method when server data is updated (import, edit, delete).
     */
    public function invalidateCache(): void
    {
        // Clear all items from the server cache pool
        $this->serverCache->clear();
    }

    /**
     * Apply sorting to query builder.
     *
     * @param array{sort: string, order: string} $sorting
     */
    private function applySorting(QueryBuilder $qb, array $sorting): void
    {
        $sortFieldMap = [
            'price' => 's.price',
            'ram' => 's.ramSizeGb',
            'storage' => 's.storageTotalGb',
            'model' => 's.model',
        ];

        $sortField = $sortFieldMap[$sorting['sort']] ?? 's.price';
        $sortOrder = strtoupper($sorting['order']) === 'DESC' ? 'DESC' : 'ASC';

        $qb->orderBy($sortField, $sortOrder);
    }

    /**
     * Apply filters to query builder.
     *
     * @param array<string, mixed> $filters
     */
    private function applyFilters(QueryBuilder $qb, array $filters): void
    {
        // Storage filter (ranges in GB)
        if (!empty($filters['storage'])) {
            $storageConditions = [];
            $paramIndex = 0;

            foreach ($filters['storage'] as $range) {
                [$min, $max] = $this->parseStorageRange($range);
                if ($max === null) {
                    $storageConditions[] = "s.storageTotalGb >= :storageMin{$paramIndex}";
                    $qb->setParameter("storageMin{$paramIndex}", $min);
                } else {
                    $storageConditions[] = "(s.storageTotalGb >= :storageMin{$paramIndex} AND s.storageTotalGb < :storageMax{$paramIndex})";
                    $qb->setParameter("storageMin{$paramIndex}", $min);
                    $qb->setParameter("storageMax{$paramIndex}", $max);
                }
                $paramIndex++;
            }

            if (!empty($storageConditions)) {
                $qb->andWhere('(' . implode(' OR ', $storageConditions) . ')');
            }
        }

        // RAM filter (exact values in GB)
        if (!empty($filters['ram'])) {
            $ramValues = array_map(fn($v) => $this->parseRamValue($v), $filters['ram']);
            $qb->andWhere('s.ramSizeGb IN (:ramValues)')
               ->setParameter('ramValues', $ramValues);
        }

        // HDD type filter
        if (!empty($filters['hddType'])) {
            $qb->andWhere('s.hddType = :hddType')
               ->setParameter('hddType', strtoupper($filters['hddType']));
        }

        // Location filter
        if (!empty($filters['location'])) {
            $qb->andWhere('s.location = :location')
               ->setParameter('location', $filters['location']);
        }

        // Price range filter
        if (isset($filters['priceMin'])) {
            $qb->andWhere('s.price >= :priceMin')
               ->setParameter('priceMin', $filters['priceMin']);
        }

        if (isset($filters['priceMax'])) {
            $qb->andWhere('s.price <= :priceMax')
               ->setParameter('priceMax', $filters['priceMax']);
        }
    }

    /**
     * Parse storage range string into min/max GB values.
     * Uses constants from ServerFilterService to avoid duplication.
     *
     * @return array{0: int, 1: int|null}
     */
    private function parseStorageRange(string $range): array
    {
        $range = strtoupper(trim($range));

        // Use centralized storage range constants
        if (isset(ServerFilterService::STORAGE_RANGES[$range])) {
            $config = ServerFilterService::STORAGE_RANGES[$range];
            return [$config['min'], $config['max']];
        }

        return [0, null];
    }

    /**
     * Parse RAM value string to integer GB.
     */
    private function parseRamValue(string $value): int
    {
        $value = strtoupper(trim($value));
        $value = preg_replace('/[^0-9]/', '', $value);
        return (int) $value;
    }

    /**
     * Get all distinct locations.
     * Results are cached.
     *
     * @return string[]
     */
    public function getDistinctLocations(): array
    {
        $cacheKey = self::CACHE_PREFIX . 'locations';
        $cacheItem = $this->serverCache->getItem($cacheKey);

        if ($cacheItem->isHit()) {
            return $cacheItem->get();
        }

        $result = $this->createQueryBuilder('s')
            ->select('DISTINCT s.location')
            ->orderBy('s.location', 'ASC')
            ->getQuery()
            ->getSingleColumnResult();

        $cacheItem->set($result);
        $cacheItem->expiresAfter(self::CACHE_TTL);
        $this->serverCache->save($cacheItem);

        return $result;
    }
}
