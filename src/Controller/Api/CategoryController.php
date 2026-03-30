<?php

namespace App\Controller\Api;

use App\DTO\Request\CategoryRequest;
use App\DTO\Response\CategoryResponse;
use App\Service\CategoryService;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/categories')]
#[OA\Tag(name: 'Category')]
class CategoryController extends AbstractController
{
    public function __construct(private readonly CategoryService $service) {}

    #[Route('', methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: 'List of categories',
        content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: new Model(type: CategoryResponse::class)))
    )]
    public function list(): JsonResponse
    {
        return $this->json($this->service->list());
    }

    #[Route('/{id}', methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: 'Single category details',
        content: new OA\JsonContent(ref: new Model(type: CategoryResponse::class))
    )]
    #[OA\Response(response: 404, description: 'Category not found')]
    public function get(int $id): JsonResponse
    {
        return $this->json($this->service->get($id));
    }

    #[Route('', methods: ['POST'])]
    #[OA\RequestBody(content: new OA\JsonContent(ref: new Model(type: CategoryRequest::class)))]
    #[OA\Response(
        response: 201,
        description: 'Created category',
        content: new OA\JsonContent(ref: new Model(type: CategoryResponse::class))
    )]
    #[OA\Response(response: 422, description: 'Validation failed')]
    public function create(
        #[MapRequestPayload] CategoryRequest $request
    ): JsonResponse {
        return $this->json($this->service->create($request), Response::HTTP_CREATED);
    }

    #[Route('/{id}', methods: ['PUT'])]
    #[OA\RequestBody(content: new OA\JsonContent(ref: new Model(type: CategoryRequest::class)))]
    #[OA\Response(
        response: 200,
        description: 'Updated category',
        content: new OA\JsonContent(ref: new Model(type: CategoryResponse::class))
    )]
    #[OA\Response(response: 404, description: 'Category not found')]
    #[OA\Response(response: 422, description: 'Validation failed')]
    public function update(
        int $id,
        #[MapRequestPayload] CategoryRequest $request
    ): JsonResponse {
        return $this->json($this->service->update($id, $request));
    }

    #[Route('/{id}', methods: ['DELETE'])]
    #[OA\Response(response: 204, description: 'Category deleted')]
    #[OA\Response(response: 404, description: 'Category not found')]
    public function delete(int $id): JsonResponse
    {
        $this->service->delete($id);
        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
