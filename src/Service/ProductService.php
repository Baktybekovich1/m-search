<?php

namespace App\Service;

use App\DTO\Request\ProductRequest;
use App\DTO\Response\ProductResponse;
use App\Entity\Product;
use App\Entity\User;
use App\Factory\ProductFactory;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ProductService
{
    public function __construct(
        private readonly ProductFactory $factory,
        private readonly ProductRepository $repository,
        private readonly EntityManagerInterface $entityManager,
        private readonly Security $security
    ) {}

    /**
     * @return array<ProductResponse>
     */
    public function list(): array
    {
        return array_map(
            fn(Product $product) => $this->factory->createResponse($product),
            $this->repository->findAll()
        );
    }

    public function get(int $id): ProductResponse
    {
        $product = $this->getProductOrThrow($id);
        return $this->factory->createResponse($product);
    }

    public function create(ProductRequest $request): ProductResponse
    {
        /** @var User|null $user */
        $user = $this->security->getUser();
        if (!$user) {
            throw new AccessDeniedHttpException('You must be logged in to create a product.');
        }

        $product = $this->factory->createEntityFromRequest($request);
        $product->setOwner($user);

        $this->entityManager->persist($product);
        $this->entityManager->flush();

        return $this->factory->createResponse($product);
    }

    public function update(int $id, ProductRequest $request): ProductResponse
    {
        $product = $this->getProductOrThrow($id);
        $this->checkOwnership($product);

        $this->factory->updateEntityFromRequest($product, $request);
        $this->entityManager->flush();

        return $this->factory->createResponse($product);
    }

    public function delete(int $id): void
    {
        $product = $this->getProductOrThrow($id);
        $this->checkOwnership($product);

        $this->entityManager->remove($product);
        $this->entityManager->flush();
    }

    private function getProductOrThrow(int $id): Product
    {
        $product = $this->repository->find($id);
        if (!$product) {
            throw new NotFoundHttpException('Product not found.');
        }
        return $product;
    }

    private function checkOwnership(Product $product): void
    {
        $user = $this->security->getUser();
        if ($product->getOwner() !== $user && !$this->security->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedHttpException('You do not have permission to modify this product.');
        }
    }
}
