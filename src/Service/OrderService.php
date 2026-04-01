<?php

namespace App\Service;

use App\Entity\Order;
use App\Entity\User;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class OrderService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ProductRepository $productRepository,
        private readonly MailerInterface $mailer
    ) {}

    public function createOrder(User $user, array $productIds, string $address): Order
    {
        $order = new Order();
        $order->setUser($user);
        $order->setAddress($address);
        $order->setStatus('completed'); // По умолчанию считаем оплаченным/завершенным
        
        $totalAmount = 0;
        foreach ($productIds as $productId) {
            $product = $this->productRepository->find($productId);
            if ($product) {
                $order->addProduct($product);
                $totalAmount += ($product->getPrice() ?? 0);
            }
        }
        
        $order->setAmount($totalAmount);
        
        $this->entityManager->persist($order);
        $this->entityManager->flush();
        
        $this->sendConfirmationEmail($order);
        
        return $order;
    }

    private function sendConfirmationEmail(Order $order): void
    {
        $user = $order->getUser();
        $products = $order->getProducts();
        $productLines = [];
        foreach ($products as $index => $product) {
            $productLines[] = sprintf("%d. %s", $index + 1, $product->getTitle());
        }
        
        $amountFormatted = number_format($order->getAmount() / 100, 2, '.', '');
        
        $message = sprintf(
            "Здравствуйте!\n\nВаш заказ №%d успешно оформлен.\n\n" .
            "Товары в заказе:\n%s\n\n" .
            "Адрес доставки:\n%s\n\n" .
            "Итоговая сумма: %s %s\n\n" .
            "Ожидайте доставки по указанному адресу. Мы свяжемся с вами в ближайшее время.\n\n" .
            "Спасибо за покупку!",
            $order->getId(),
            implode("\n", $productLines),
            $order->getAddress(),
            $amountFormatted,
            strtoupper($order->getCurrency() ?? 'USD')
        );

        $email = (new Email())
            ->from('noreply@msearch.com')
            ->to($user->getEmail())
            ->subject('Заказ успешно оформлен')
            ->text($message);

        $this->mailer->send($email);
    }
}
