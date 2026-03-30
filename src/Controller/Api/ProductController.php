<?php

namespace App\Controller\Api;

use App\DTO\Request\ProductRequest;
use App\DTO\Response\ProductResponse;
use App\Service\ProductService;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/products')]
#[OA\Tag(name: 'Product')]
class ProductController extends AbstractController
{
    public function __construct(private readonly ProductService $service) {}

    #[Route('', methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: 'List of products',
        content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: new Model(type: ProductResponse::class)))
    )]
    public function list(): JsonResponse
    {
        return $this->json($this->service->list());
    }

    #[Route('/{id}', methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: 'Single product details',
        content: new OA\JsonContent(ref: new Model(type: ProductResponse::class))
    )]
    #[OA\Response(response: 404, description: 'Product not found')]
    public function get(int $id): JsonResponse
    {
        return $this->json($this->service->get($id));
    }

    #[Route('', methods: ['POST'])]
    #[OA\RequestBody(content: new OA\JsonContent(ref: new Model(type: ProductRequest::class)))]
    #[OA\Response(
        response: 201,
        description: 'Created product',
        content: new OA\JsonContent(ref: new Model(type: ProductResponse::class))
    )]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Access Denied')]
    #[OA\Response(response: 422, description: 'Validation failed')]
    public function create(
        #[MapRequestPayload] ProductRequest $request
    ): JsonResponse {
        return $this->json($this->service->create($request), Response::HTTP_CREATED);
    }

    #[Route('/{id}', methods: ['PUT'])]
    #[OA\RequestBody(content: new OA\JsonContent(ref: new Model(type: ProductRequest::class)))]
    #[OA\Response(
        response: 200,
        description: 'Updated product',
        content: new OA\JsonContent(ref: new Model(type: ProductResponse::class))
    )]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Access Denied')]
    #[OA\Response(response: 404, description: 'Product not found')]
    #[OA\Response(response: 422, description: 'Validation failed')]
    public function update(
        int $id,
        #[MapRequestPayload] ProductRequest $request
    ): JsonResponse {
        return $this->json($this->service->update($id, $request));
    }

    #[Route('/{id}', methods: ['DELETE'])]
    #[OA\Response(response: 204, description: 'Product deleted')]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Access Denied')]
    #[OA\Response(response: 404, description: 'Product not found')]
    public function delete(int $id): JsonResponse
    {
        $this->service->delete($id);
        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
