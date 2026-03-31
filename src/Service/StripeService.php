<?php

namespace App\Service;

use App\Entity\Payment;
use App\Entity\Product;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\Checkout\Session;
use Stripe\Stripe;
use Stripe\Webhook;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class StripeService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly MailerInterface $mailer,
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
            'shipping_address_collection' => [
                'allowed_countries' => ['US', 'CA', 'GB', 'RU', 'KZ', 'KG', 'UZ', 'UA', 'BY', 'DE', 'FR', 'IT', 'ES', 'CN', 'JP', 'IN', 'TR'],
            ],
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

                $shipping = $session->shipping_details;
                $address = $shipping ? $shipping->address : null;
                $addressParts = [];
                if ($address) {
                    if ($address->line1) $addressParts[] = $address->line1;
                    if ($address->line2) $addressParts[] = $address->line2;
                    if ($address->city) $addressParts[] = $address->city;
                    if ($address->state) $addressParts[] = $address->state;
                    if ($address->postal_code) $addressParts[] = $address->postal_code;
                    if ($address->country) $addressParts[] = $address->country;
                }
                $addressString = !empty($addressParts) ? implode(', ', $addressParts) : 'Не указан';

                $customerEmail = $session->customer_details->email ?? $session->customer_email;
                $productName = $payment->getProduct()->getTitle();

                if ($customerEmail) {
                    $email = (new Email())
                        ->from('noreply@msearch.com')
                        ->to($customerEmail)
                        ->subject('Заказ принят')
                        ->text(sprintf(
                            "Ваш заказ принят.\n\nВы купили: %s\nОжидайте товар по адресу: %s\n\nСпасибо за покупку!",
                            $productName,
                            $addressString
                        ));

                    $this->mailer->send($email);
                }
            }
        }
    }
}
