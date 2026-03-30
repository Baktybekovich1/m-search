<?php

namespace App\Service;

use App\DTO\Response\ProductResponse;
use App\Entity\Product;
use App\Factory\ProductFactory;
use App\Repository\ProductRepository;

/**
 * Uses a Gemini-generated text description to search for matching products.
 */
class AiSearchService
{
    public function __construct(
        private readonly GeminiService $geminiService,
        private readonly ProductRepository $productRepository,
        private readonly ProductFactory $productFactory
    ) {}

    /**
     * Upload an image file, describe it with Gemini, then full-text search products.
     *
     * @param string $imagePath
     * @param string $mimeType
     * @return ProductResponse[]
     */
    public function searchByImage(string $imagePath, string $mimeType): array
    {
        $description = $this->geminiService->describeImage($imagePath, $mimeType);

        $products = $this->productRepository->searchByText($description);

        return array_map(
            fn(Product $product) => $this->productFactory->createResponse($product),
            $products
        );
    }
}
