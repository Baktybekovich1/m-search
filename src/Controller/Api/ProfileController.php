<?php

namespace App\Controller\Api;

use App\DTO\Request\ProfileUpdateRequest;
use App\DTO\Response\ProfileResponse;
use App\Service\ProfileService;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/profile')]
#[OA\Tag(name: 'Profile')]
class ProfileController extends AbstractController
{
    public function __construct(private readonly ProfileService $service) {}

    #[Route('', methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: 'Get current user profile',
        content: new OA\JsonContent(ref: new Model(type: ProfileResponse::class))
    )]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    public function get(): JsonResponse
    {
        return $this->json($this->service->getProfile());
    }

    #[Route('', methods: ['PUT'])]
    #[OA\RequestBody(content: new OA\JsonContent(ref: new Model(type: ProfileUpdateRequest::class)))]
    #[OA\Response(
        response: 200,
        description: 'Updated profile',
        content: new OA\JsonContent(ref: new Model(type: ProfileResponse::class))
    )]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 422, description: 'Validation failed')]
    public function update(
        #[MapRequestPayload] ProfileUpdateRequest $request
    ): JsonResponse {
        return $this->json($this->service->updateProfile($request));
    }
}
