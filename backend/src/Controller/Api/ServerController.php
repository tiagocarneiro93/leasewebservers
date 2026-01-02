<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Repository\ServerRepository;
use App\Service\ServerFilterService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Server API Controller.
 *
 * OpenAPI documentation is defined in config/packages/nelmio_api_doc.yaml
 * to keep this controller focused on business logic.
 *
 * Caching strategy:
 * - Application cache: Results cached in ServerRepository (1 hour TTL)
 * - HTTP cache: Browser/CDN caching via Cache-Control headers (5 min)
 */
#[Route('/api', name: 'api_')]
class ServerController extends AbstractController
{
    /**
     * HTTP cache max-age for browser caching (5 minutes).
     */
    private const HTTP_CACHE_MAX_AGE = 300;

    /**
     * HTTP cache shared max-age for CDN/proxy caching (10 minutes).
     */
    private const HTTP_CACHE_SHARED_MAX_AGE = 600;

    public function __construct(
        private readonly ServerRepository $serverRepository,
        private readonly ServerFilterService $filterService,
        private readonly SerializerInterface $serializer
    ) {
    }

    /**
     * Get list of servers with optional filters.
     */
    #[Route('/servers', name: 'servers_index', methods: ['GET'])]
    public function index(Request $request): JsonResponse
    {
        $filters = $this->filterService->extractFilters($request);
        $pagination = $this->filterService->extractPagination($request);
        $sorting = $this->filterService->extractSorting($request);

        $result = $this->serverRepository->findByFilters(
            $filters,
            $pagination['page'],
            $pagination['limit'],
            $sorting
        );

        $data = $this->serializer->normalize($result['servers'], 'json', ['groups' => 'server:list']);

        $response = $this->json([
            'data' => $data,
            'meta' => [
                'total' => $result['total'],
                'page' => $result['page'],
                'limit' => $result['limit'],
                'totalPages' => $result['totalPages'],
                'hasNextPage' => $result['page'] < $result['totalPages'],
                'hasPrevPage' => $result['page'] > 1,
                'sort' => $sorting['sort'],
                'order' => $sorting['order'],
            ],
            'filters' => $filters,
        ]);

        // Add HTTP cache headers for browser/CDN caching
        $this->addCacheHeaders($response);

        return $response;
    }

    /**
     * Get a single server by ID.
     */
    #[Route('/servers/{id}', name: 'servers_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id): JsonResponse
    {
        $server = $this->serverRepository->find($id);

        if (!$server) {
            return $this->json([
                'error' => 'Server not found',
                'status' => 404,
            ], Response::HTTP_NOT_FOUND);
        }

        $data = $this->serializer->normalize($server, 'json', ['groups' => 'server:detail']);

        $response = $this->json([
            'data' => $data,
        ]);

        // Add HTTP cache headers
        $this->addCacheHeaders($response);

        return $response;
    }

    /**
     * Get available filter options.
     */
    #[Route('/filters', name: 'filters', methods: ['GET'])]
    public function filters(): JsonResponse
    {
        $response = $this->json([
            'data' => $this->filterService->getAvailableFilters(),
        ]);

        // Filter options rarely change - cache longer
        $this->addCacheHeaders($response, self::HTTP_CACHE_MAX_AGE * 2);

        return $response;
    }

    /**
     * Add HTTP cache headers to response.
     * Enables browser and CDN/proxy caching for better performance.
     */
    private function addCacheHeaders(JsonResponse $response, ?int $maxAge = null): void
    {
        $maxAge = $maxAge ?? self::HTTP_CACHE_MAX_AGE;

        // Allow public caching (browsers, CDNs, proxies)
        $response->setPublic();

        // Browser cache duration
        $response->setMaxAge($maxAge);

        // CDN/proxy cache duration (can be longer than browser cache)
        $response->setSharedMaxAge(self::HTTP_CACHE_SHARED_MAX_AGE);

        // ETag for conditional requests (304 Not Modified)
        $response->setEtag(md5($response->getContent()));

        // Vary header - cache varies by these request headers
        $response->headers->set('Vary', 'Accept, Accept-Encoding');
    }
}
