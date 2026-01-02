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
 * OpenAPI documentation is in src/OpenApi/ServerApiDoc.php
 * to keep this controller focused on business logic.
 */
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

        return $this->json([
            'data' => $data,
        ]);
    }

    /**
     * Get available filter options.
     */
    #[Route('/filters', name: 'filters', methods: ['GET'])]
    public function filters(): JsonResponse
    {
        return $this->json([
            'data' => $this->filterService->getAvailableFilters(),
        ]);
    }
}
