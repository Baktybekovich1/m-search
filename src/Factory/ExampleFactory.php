<?php

namespace App\Factory;

use App\DTO\Request\ExampleRequest;
use App\DTO\Response\ExampleResponse;
// use App\Entity\Example;

class ExampleFactory
{
    // Example of creating an entity from a request DTO
    // public function createEntityFromRequest(ExampleRequest $request): Example
    // {
    //     $example = new Example();
    //     $example->setName($request->name);
    //     $example->setEmail($request->email);
    //     return $example;
    // }

    // Example of creating a response DTO
    public function createResponse(string $id, string $name): ExampleResponse
    {
        return new ExampleResponse(
            id: $id,
            message: sprintf('Hello %s, your request was processed successfully.', $name)
        );
    }
}
