<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Repository\ServerRepository;
use App\Service\ServerFilterService;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api', name: 'api_')]
class ServerController extends AbstractController
{
    public function __construct(
        private readonly ServerRepository $serverRepository,
        private readonly ServerFilterService $filterService,
        private readonly SerializerInterface $serializer
    ) {
    }

    /**
     * Get list of servers with optional filters
     */
    #[Route('/servers', name: 'servers_index', methods: ['GET'])]
    #[OA\Get(
        path: '/api/servers',
        summary: 'Get list of servers',
        description: 'Returns a paginated list of servers with optional filtering',
        tags: ['Servers']
    )]
    #[OA\Parameter(
        name: 'storage[]',
        description: 'Storage range filter (e.g., 0-250GB, 250GB-500GB, 500GB-1TB, etc.)',
        in: 'query',
        required: false,
        schema: new OA\Schema(type: 'array', items: new OA\Items(type: 'string'))
    )]
    #[OA\Parameter(
        name: 'ram[]',
        description: 'RAM size filter in GB (e.g., 2GB, 4GB, 8GB, 16GB, 32GB, 64GB)',
        in: 'query',
        required: false,
        schema: new OA\Schema(type: 'array', items: new OA\Items(type: 'string'))
    )]
    #[OA\Parameter(
        name: 'hddType',
        description: 'HDD type filter (SAS, SATA, or SSD)',
        in: 'query',
        required: false,
        schema: new OA\Schema(type: 'string', enum: ['SAS', 'SATA', 'SSD'])
    )]
    #[OA\Parameter(
        name: 'location',
        description: 'Location filter',
        in: 'query',
        required: false,
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Parameter(
        name: 'page',
        description: 'Page number (default: 1)',
        in: 'query',
        required: false,
        schema: new OA\Schema(type: 'integer', default: 1)
    )]
    #[OA\Parameter(
        name: 'limit',
        description: 'Items per page (default: 20, max: 100)',
        in: 'query',
        required: false,
        schema: new OA\Schema(type: 'integer', default: 20, maximum: 100)
    )]
    #[OA\Parameter(
        name: 'priceMin',
        description: 'Minimum price filter (in EUR)',
        in: 'query',
        required: false,
        schema: new OA\Schema(type: 'number', minimum: 0)
    )]
    #[OA\Parameter(
        name: 'priceMax',
        description: 'Maximum price filter (in EUR)',
        in: 'query',
        required: false,
        schema: new OA\Schema(type: 'number', minimum: 0)
    )]
    #[OA\Parameter(
        name: 'sort',
        description: 'Sort field (default: price)',
        in: 'query',
        required: false,
        schema: new OA\Schema(type: 'string', enum: ['price', 'ram', 'storage', 'model'], default: 'price')
    )]
    #[OA\Parameter(
        name: 'order',
        description: 'Sort order (default: asc)',
        in: 'query',
        required: false,
        schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'], default: 'asc')
    )]
    #[OA\Response(
        response: 200,
        description: 'Successful response with server list',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object')),
                new OA\Property(property: 'meta', type: 'object', properties: [
                    new OA\Property(property: 'total', type: 'integer'),
                    new OA\Property(property: 'page', type: 'integer'),
                    new OA\Property(property: 'limit', type: 'integer'),
                    new OA\Property(property: 'totalPages', type: 'integer'),
                    new OA\Property(property: 'hasNextPage', type: 'boolean'),
                    new OA\Property(property: 'hasPrevPage', type: 'boolean'),
                ]),
                new OA\Property(property: 'filters', type: 'object'),
            ]
        )
    )]
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

        return $this->json([
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
    }

    /**
     * Get a single server by ID
     */
    #[Route('/servers/{id}', name: 'servers_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    #[OA\Get(
        path: '/api/servers/{id}',
        summary: 'Get server details',
        description: 'Returns details of a specific server',
        tags: ['Servers']
    )]
    #[OA\Parameter(
        name: 'id',
        description: 'Server ID',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 200,
        description: 'Successful response with server details',
        content: new OA\JsonContent(type: 'object', properties: [
            new OA\Property(property: 'data', type: 'object'),
        ])
    )]
    #[OA\Response(
        response: 404,
        description: 'Server not found'
    )]
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

        return $this->json([
            'data' => $data,
        ]);
    }

    /**
     * Get available filter options
     */
    #[Route('/filters', name: 'filters', methods: ['GET'])]
    #[OA\Get(
        path: '/api/filters',
        summary: 'Get available filter options',
        description: 'Returns all available filter options for the server list',
        tags: ['Filters']
    )]
    #[OA\Response(
        response: 200,
        description: 'Successful response with filter options',
        content: new OA\JsonContent(type: 'object', properties: [
            new OA\Property(property: 'data', type: 'object', properties: [
                new OA\Property(property: 'storage', type: 'array', items: new OA\Items(type: 'object')),
                new OA\Property(property: 'ram', type: 'array', items: new OA\Items(type: 'object')),
                new OA\Property(property: 'hddType', type: 'array', items: new OA\Items(type: 'string')),
                new OA\Property(property: 'location', type: 'array', items: new OA\Items(type: 'string')),
            ]),
        ])
    )]
    public function filters(): JsonResponse
    {
        return $this->json([
            'data' => $this->filterService->getAvailableFilters(),
        ]);
    }
}

