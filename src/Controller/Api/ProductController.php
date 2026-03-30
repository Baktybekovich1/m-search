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

    #[Route('/upload', methods: ['POST'])]
    #[OA\RequestBody(
        content: new OA\MediaType(
            mediaType: 'multipart/form-data',
            schema: new OA\Schema(
                properties: [
                    new OA\Property(property: 'title', type: 'string'),
                    new OA\Property(property: 'description', type: 'string'),
                    new OA\Property(property: 'price', type: 'integer'),
                    new OA\Property(property: 'categoryIds', type: 'array', items: new OA\Items(type: 'integer')),
                    new OA\Property(property: 'mainPhoto', type: 'string', format: 'binary'),
                    new OA\Property(property: 'auxiliaryPhotos', type: 'array', items: new OA\Items(type: 'string', format: 'binary')),
                ]
            )
        )
    )]
    #[OA\Response(response: 201, description: 'Created product', content: new OA\JsonContent(ref: new Model(type: ProductResponse::class)))]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Access Denied')]
    #[OA\Response(response: 422, description: 'Validation failed')]
    public function createWithUploads(
        \Symfony\Component\HttpFoundation\Request $symfonyRequest,
        \App\Service\FileUploader $fileUploader,
        \Symfony\Component\Validator\Validator\ValidatorInterface $validator
    ): JsonResponse {
        $title = $symfonyRequest->request->get('title');
        $description = $symfonyRequest->request->get('description');
        $price = $symfonyRequest->request->get('price');
        $categoryIds = $symfonyRequest->request->all('categoryIds');
        
        $mainPhotoFile = $symfonyRequest->files->get('mainPhoto');
        $auxiliaryPhotoFiles = $symfonyRequest->files->all('auxiliaryPhotos');

        if (!$mainPhotoFile instanceof \Symfony\Component\HttpFoundation\File\UploadedFile) {
            return $this->json(['error' => 'mainPhoto is required and must be a valid file'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $mainPhotoUrl = $fileUploader->upload($mainPhotoFile);
        $auxiliaryPhotoUrls = [];
        foreach ($auxiliaryPhotoFiles as $file) {
            if ($file instanceof \Symfony\Component\HttpFoundation\File\UploadedFile) {
                $auxiliaryPhotoUrls[] = $fileUploader->upload($file);
            }
        }

        $dto = new ProductRequest(
            title: (string)$title,
            description: (string)$description,
            mainPhoto: $mainPhotoUrl,
            auxiliaryPhotos: $auxiliaryPhotoUrls,
            categoryIds: array_map('intval', (array)$categoryIds),
            price: $price !== null ? (int)$price : null
        );

        $errors = $validator->validate($dto);
        if (count($errors) > 0) {
            return $this->json($errors, Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->json($this->service->create($dto), Response::HTTP_CREATED);
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
