<?php

namespace App\Controller\Api;

use App\Entity\Product;
use App\Service\StripeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;

#[Route('/api/payments')]
#[OA\Tag(name: 'Payments')]
class PaymentController extends AbstractController
{
    public function __construct(
        private readonly StripeService $stripeService,
        private readonly EntityManagerInterface $entityManager
    ) {}

    #[Route('/checkout/{productId}', methods: ['POST'])]
    #[OA\Post(summary: 'Create Stripe Checkout Session')]
    #[OA\Response(response: 200, description: 'Checkout URL')]
    public function checkout(int $productId): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $product = $this->entityManager->getRepository(Product::class)->find($productId);
        if (!$product) {
            return $this->json(['error' => 'Product not found'], 404);
        }

        $successUrl = $this->generateUrl('payment_success', [], 0); // Placeholder
        $cancelUrl = $this->generateUrl('payment_cancel', [], 0);   // Placeholder

        $url = $this->stripeService->createCheckoutSession(
            $product,
            $user,
            'http://localhost:8000/api/payments/success', // Simple static URLs for test
            'http://localhost:8000/api/payments/cancel'
        );

        return $this->json(['checkoutUrl' => $url]);
    }

    #[Route('/history', methods: ['GET'])]
    #[OA\Get(summary: 'Get user payment history')]
    public function history(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $payments = $user->getPayments();
        $data = [];

        foreach ($payments as $payment) {
            $data[] = [
                'id' => $payment->getId(),
                'product' => $payment->getProduct()->getTitle(),
                'amount' => $payment->getAmount() / 100,
                'currency' => $payment->getCurrency(),
                'status' => $payment->getStatus(),
                'createdAt' => $payment->getCreatedAt()->format('Y-m-d H:i:s'),
            ];
        }

        return $this->json($data);
    }

    #[Route('/success', name: 'payment_success', methods: ['GET'])]
    public function success(): JsonResponse
    {
        return $this->json(['message' => 'Payment successful!']);
    }

    #[Route('/cancel', name: 'payment_cancel', methods: ['GET'])]
    public function cancel(): JsonResponse
    {
        return $this->json(['message' => 'Payment cancelled.']);
    }
}
