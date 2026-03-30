<?php

namespace App\Controller\Api;

use App\Service\AiSearchService;
use App\Service\GeminiService;
use App\DTO\Response\ProductResponse;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/search')]
#[OA\Tag(name: 'Search')]
class SearchController extends AbstractController
{
    public function __construct(
        private readonly AiSearchService $aiSearchService,
        private readonly GeminiService $geminiService
    ) {}

    /**
     * Upload a photo and find matching products using Gemini AI.
     */
    #[Route('/image', methods: ['POST'])]
    #[OA\RequestBody(
        required: true,
        content: new OA\MediaType(
            mediaType: 'multipart/form-data',
            schema: new OA\Schema(
                type: 'object',
                required: ['image'],
                properties: [
                    new OA\Property(
                        property: 'image',
                        type: 'string',
                        format: 'binary',
                        description: 'Photo to search by (jpeg/png/webp/gif)'
                    ),
                ]
            )
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'List of matching products',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'query', type: 'string', description: 'Gemini description used for search'),
                new OA\Property(
                    property: 'results',
                    type: 'array',
                    items: new OA\Items(ref: new Model(type: ProductResponse::class))
                ),
            ]
        )
    )]
    #[OA\Response(response: 400, description: 'No image uploaded or invalid format')]
    public function searchByImage(Request $request): JsonResponse
    {
        $file = $request->files->get('image');

        if (!$file) {
            throw new BadRequestHttpException('No image file uploaded. Use multipart/form-data with field "image".');
        }

        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        $mimeType = $file->getMimeType();

        if (!in_array($mimeType, $allowedMimes, true)) {
            throw new BadRequestHttpException(
                sprintf('Unsupported image type "%s". Allowed: %s', $mimeType, implode(', ', $allowedMimes))
            );
        }

        $products = $this->aiSearchService->searchByImage($file->getPathname(), $mimeType);

        return $this->json([
            'results' => $products,
        ]);
    }

    /**
     * Test endpoint: upload a photo and get the raw Gemini description (no DB search).
     */
    #[Route('/describe', methods: ['POST'])]
    #[OA\RequestBody(
        required: true,
        content: new OA\MediaType(
            mediaType: 'multipart/form-data',
            schema: new OA\Schema(
                type: 'object',
                required: ['image'],
                properties: [
                    new OA\Property(property: 'image', type: 'string', format: 'binary')
                ]
            )
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Gemini description of the uploaded image',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'description', type: 'string')
            ]
        )
    )]
    #[OA\Response(response: 400, description: 'No image or invalid format')]
    public function describe(Request $request): JsonResponse
    {
        $file = $request->files->get('image');

        if (!$file) {
            throw new BadRequestHttpException('No image file uploaded.');
        }

        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        $mimeType = $file->getMimeType();

        if (!in_array($mimeType, $allowedMimes, true)) {
            throw new BadRequestHttpException(
                sprintf('Unsupported type "%s". Allowed: %s', $mimeType, implode(', ', $allowedMimes))
            );
        }

        $description = $this->geminiService->describeImage($file->getPathname(), $mimeType);

        return $this->json(['description' => $description]);
    }
}
