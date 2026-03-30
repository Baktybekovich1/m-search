<?php

namespace App\Controller\Api;

use App\DTO\Request\ExampleRequest;
use App\DTO\Response\ExampleResponse;
use App\Service\ExampleService;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/example')]
#[OA\Tag(name: 'Example')]
class ExampleController extends AbstractController
{
    #[Route('', methods: ['POST'])]
    #[OA\Post(
        summary: 'Create an example resource',
        description: 'Receives an ExampleRequest, validates it, and returns an ExampleResponse',
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(ref: new Model(type: ExampleRequest::class))
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successful response',
                content: new OA\JsonContent(ref: new Model(type: ExampleResponse::class))
            ),
            new OA\Response(
                response: 422,
                description: 'Validation failed'
            ),
        ]
    )]
    public function create(
        // MapRequestPayload automatically deserializes JSON and validates it using Symfony Validator
        #[MapRequestPayload] ExampleRequest $request,
        ExampleService $exampleService
    ): JsonResponse {
        // Pass the DTO to the Service layer for business logic
        $response = $exampleService->processExample($request);

        // Return the Response DTO as JSON
        return $this->json($response);
    }
}
