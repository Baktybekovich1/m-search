<?php

namespace App\Controller\Api;

use App\DTO\Request\CreateOrderRequest;
use App\Service\OrderService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;

#[Route('/api/orders')]
#[OA\Tag(name: 'Orders')]
class OrderController extends AbstractController
{
    public function __construct(
        private readonly OrderService $orderService
    ) {}

    #[Route('', methods: ['POST'])]
    #[OA\Post(summary: 'Create a new order')]
    #[OA\RequestBody(
        content: new OA\JsonContent(
            ref: '#/components/schemas/CreateOrderRequest'
        )
    )]
    #[OA\Response(response: 201, description: 'Order created')]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    public function create(
        #[MapRequestPayload] CreateOrderRequest $request
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $order = $this->orderService->createOrder(
            $user,
            $request->productIds,
            $request->address
        );

        return $this->json([
            'message' => 'Заказ успешно оформлен! Ожидайте доставки по адресу: ' . $order->getAddress(),
            'orderId' => $order->getId(),
            'amount' => $order->getAmount() / 100,
            'status' => $order->getStatus()
        ], 201);
    }

    #[Route('/history', methods: ['GET'])]
    #[OA\Get(summary: 'Get user order history')]
    #[OA\Response(response: 200, description: 'List of orders')]
    public function history(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $orders = $user->getOrders();
        $data = [];

        foreach ($orders as $order) {
            $products = [];
            foreach ($order->getProducts() as $product) {
                $products[] = [
                    'id' => $product->getId(),
                    'title' => $product->getTitle()
                ];
            }

            $data[] = [
                'id' => $order->getId(),
                'products' => $products,
                'amount' => $order->getAmount() / 100,
                'currency' => $order->getCurrency(),
                'status' => $order->getStatus(),
                'address' => $order->getAddress(),
                'createdAt' => $order->getCreatedAt()->format('Y-m-d H:i:s'),
            ];
        }

        return $this->json($data);
    }
}
