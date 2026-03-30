<?php

namespace App\Factory;

use App\DTO\Request\ProductRequest;
use App\DTO\Response\ProductResponse;
use App\Entity\Category;
use App\Entity\Product;
use App\Repository\CategoryRepository;

class ProductFactory
{
    public function __construct(
        private readonly CategoryRepository $categoryRepository,
        private readonly CategoryFactory $categoryFactory
    ) {}

    public function createEntityFromRequest(ProductRequest $request): Product
    {
        $product = new Product();
        $this->updateEntityFromRequest($product, $request);
        return $product;
    }

    public function updateEntityFromRequest(Product $product, ProductRequest $request): void
    {
        $product->setTitle($request->title);
        $product->setDescription($request->description);
        $product->setMainPhoto($request->mainPhoto);
        $product->setAuxiliaryPhotos($request->auxiliaryPhotos);
        $product->setPrice($request->price);

        // Update categories
        $currentCategories = $product->getCategories()->toArray();
        $newCategories = [];

        foreach ($request->categoryIds as $categoryId) {
            $category = $this->categoryRepository->find($categoryId);
            if ($category) {
                $newCategories[] = $category;
                if (!in_array($category, $currentCategories, true)) {
                    $product->addCategory($category);
                }
            }
        }

        // Remove categories that are no longer selected
        foreach ($currentCategories as $currentCategory) {
            if (!in_array($currentCategory, $newCategories, true)) {
                $product->removeCategory($currentCategory);
            }
        }
    }

    public function createResponse(Product $product): ProductResponse
    {
        $categories = array_map(
            fn(Category $category) => $this->categoryFactory->createResponse($category),
            $product->getCategories()->toArray()
        );

        return new ProductResponse(
            id: $product->getId(),
            title: $product->getTitle(),
            description: $product->getDescription(),
            mainPhoto: $product->getMainPhoto(),
            auxiliaryPhotos: $product->getAuxiliaryPhotos(),
            categories: $categories,
            ownerId: $product->getOwner()?->getId(),
            price: $product->getPrice()
        );
    }
}
