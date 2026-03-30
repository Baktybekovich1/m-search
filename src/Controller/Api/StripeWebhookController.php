<?php

namespace App\Controller\Api;

use App\Service\StripeService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Payments')]
class StripeWebhookController extends AbstractController
{
    #[Route('/api/payments/webhook', methods: ['POST'])]
    public function handle(Request $request, StripeService $stripeService): Response
    {
        $payload = $request->getContent();
        $sigHeader = $request->headers->get('stripe-signature');

        if (!$sigHeader) {
            return new Response('Missing signature', 400);
        }

        try {
            $stripeService->handleWebhook($payload, $sigHeader);
        } catch (\RuntimeException $e) {
            return new Response($e->getMessage(), 400);
        }

        return new Response('Webhook handled', 200);
    }
}
