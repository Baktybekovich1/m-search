<?php

namespace App\Service;

use App\Entity\Payment;
use App\Entity\Product;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\Checkout\Session;
use Stripe\Stripe;
use Stripe\Webhook;

class StripeService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly string $stripeSecretKey,
        private readonly string $webhookSecret
    ) {
        Stripe::setApiKey($this->stripeSecretKey);
    }

    public function createCheckoutSession(Product $product, User $user, string $successUrl, string $cancelUrl): string
    {
        $session = Session::create([
            'payment_method_types' => ['card'],
            'customer_email' => $user->getEmail(),
            'line_items' => [[
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => [
                        'name' => $product->getTitle(),
                    ],
                    'unit_amount' => $product->getPrice() ?? 1000,
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'metadata' => [
                'product_id' => $product->getId(),
                'user_id' => $user->getId(),
            ],
        ]);

        $payment = new Payment();
        $payment->setUser($user);
        $payment->setProduct($product);
        $payment->setAmount($product->getPrice() ?? 1000);
        $payment->setCurrency('usd');
        $payment->setStatus('pending');
        $payment->setStripeSessionId($session->id);

        $this->entityManager->persist($payment);
        $this->entityManager->flush();

        return $session->url;
    }

    public function handleWebhook(string $payload, string $sigHeader): void
    {
        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $this->webhookSecret);
        } catch (\UnexpectedValueException|\Stripe\Exception\SignatureVerificationException $e) {
            throw new \RuntimeException('Invalid webhook signature');
        }

        if ($event->type === 'checkout.session.completed') {
            $session = $event->data->object;
            $payment = $this->entityManager->getRepository(Payment::class)->findOneBy([
                'stripeSessionId' => $session->id
            ]);

            if ($payment) {
                $payment->setStatus('completed');
                $this->entityManager->flush();
            }
        }
    }
}
