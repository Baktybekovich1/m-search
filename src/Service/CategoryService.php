<?php

namespace App\Service;

use App\DTO\Request\CategoryRequest;
use App\DTO\Response\CategoryResponse;
use App\Entity\Category;
use App\Factory\CategoryFactory;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CategoryService
{
    public function __construct(
        private readonly CategoryFactory $factory,
        private readonly CategoryRepository $repository,
        private readonly EntityManagerInterface $entityManager
    ) {}

    /**
     * @return array<CategoryResponse>
     */
    public function list(): array
    {
        return array_map(
            fn(Category $category) => $this->factory->createResponse($category),
            $this->repository->findAll()
        );
    }

    public function get(int $id): CategoryResponse
    {
        $category = $this->getCategoryOrThrow($id);
        return $this->factory->createResponse($category);
    }

    public function create(CategoryRequest $request): CategoryResponse
    {
        $category = $this->factory->createEntityFromRequest($request);
        $this->entityManager->persist($category);
        $this->entityManager->flush();
        
        return $this->factory->createResponse($category);
    }

    public function update(int $id, CategoryRequest $request): CategoryResponse
    {
        $category = $this->getCategoryOrThrow($id);
        $this->factory->updateEntityFromRequest($category, $request);
        $this->entityManager->flush();
        
        return $this->factory->createResponse($category);
    }

    public function delete(int $id): void
    {
        $category = $this->getCategoryOrThrow($id);
        $this->entityManager->remove($category);
        $this->entityManager->flush();
    }

    private function getCategoryOrThrow(int $id): Category
    {
        $category = $this->repository->find($id);
        if (!$category) {
            throw new NotFoundHttpException('Category not found.');
        }
        return $category;
    }
}
