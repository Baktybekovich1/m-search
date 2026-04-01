<?php
namespace App\Controller\Api;

use App\DTO\Request\ForgotPasswordRequest;
use App\DTO\Request\RegisterRequest;
use App\DTO\Request\ResetPasswordRequest;
use App\DTO\Request\VerifyRequest;
use App\Service\AuthService;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/auth')]
#[OA\Tag(name: 'Auth')]
class AuthController extends AbstractController {
    
    #[Route('/register', methods: ['POST'])]
    #[OA\Post(
        summary: 'Register a new user',
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(ref: new Model(type: RegisterRequest::class))
        )
    )]
    public function register(
        #[MapRequestPayload] RegisterRequest $request,
        AuthService $authService
    ): JsonResponse {
        $authService->register($request);
        return $this->json(['message' => 'Код подтверждения отправлен на email']);
    }

    #[Route('/verify', methods: ['POST'])]
    #[OA\Post(
        summary: 'Verify user email',
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(ref: new Model(type: VerifyRequest::class))
        )
    )]
    public function verify(
        #[MapRequestPayload] VerifyRequest $request,
        AuthService $authService
    ): JsonResponse {
        $authService->verify($request);
        return $this->json(['message' => 'Email успешно подтвержден']);
    }

    #[Route('/login', name: 'api_login_check', methods: ['POST'])]
    #[OA\Post(
        summary: 'Login to get JWT token',
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'email', type: 'string', example: 'user@example.com'),
                    new OA\Property(property: 'password', type: 'string', example: 'password')
                ]
            )
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Returns JWT token',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'token', type: 'string')
            ]
        )
    )]
    public function login(): void {
        // This endpoint is handled by LexikJWTAuthenticationBundle
    }

    #[Route('/forgot-password', methods: ['POST'])]
    #[OA\Post(
        summary: 'Request password reset code',
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(ref: new Model(type: ForgotPasswordRequest::class))
        )
    )]
    public function forgotPassword(
        #[MapRequestPayload] ForgotPasswordRequest $request,
        AuthService $authService
    ): JsonResponse {
        $authService->forgotPassword($request);
        return $this->json(['message' => 'Если email найден, код восстановления отправлен']);
    }

    #[Route('/reset-password', methods: ['POST'])]
    #[OA\Post(
        summary: 'Reset password using the code',
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(ref: new Model(type: ResetPasswordRequest::class))
        )
    )]
    public function resetPassword(
        #[MapRequestPayload] ResetPasswordRequest $request,
        AuthService $authService
    ): JsonResponse {
        $authService->resetPassword($request);
        return $this->json(['message' => 'Пароль успешно изменен']);
    }
}
