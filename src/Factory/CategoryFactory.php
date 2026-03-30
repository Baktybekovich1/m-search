<?php

namespace App\Factory;

use App\DTO\Request\CategoryRequest;
use App\DTO\Response\CategoryResponse;
use App\Entity\Category;

class CategoryFactory
{
    public function createEntityFromRequest(CategoryRequest $request): Category
    {
        $category = new Category();
        $this->updateEntityFromRequest($category, $request);
        return $category;
    }

    public function updateEntityFromRequest(Category $category, CategoryRequest $request): void
    {
        $category->setName($request->name);
    }

    public function createResponse(Category $category): CategoryResponse
    {
        return new CategoryResponse(
            $category->getId(),
            $category->getName()
        );
    }
}
