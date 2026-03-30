<?php

namespace App\Service;

use App\DTO\Request\ExampleRequest;
use App\DTO\Response\ExampleResponse;
use App\Exception\ApiException;
use App\Factory\ExampleFactory;
// use Doctrine\ORM\EntityManagerInterface;

class ExampleService
{
    public function __construct(
        private readonly ExampleFactory $exampleFactory,
        // private readonly EntityManagerInterface $entityManager
    ) {
    }

    public function processExample(ExampleRequest $request): ExampleResponse
    {
        // 1. Business logic and custom validation
        if ($request->name === 'error') {
            throw new ApiException('This name is not allowed', 422);
        }

        // 2. Create Entity using Factory
        // $entity = $this->exampleFactory->createEntityFromRequest($request);
        
        // 3. Save to database using EntityManager or Repository
        // $this->entityManager->persist($entity);
        // $this->entityManager->flush();

        // 4. Return a Response DTO
        $fakeId = uniqid();
        return $this->exampleFactory->createResponse($fakeId, $request->name);
    }
}
