<?php

require 'vendor/autoload.php';

use App\Service\GeminiService;
use Symfony\Component\HttpClient\HttpClient;
use Psr\Log\NullLogger;

try {
    $httpClient = HttpClient::create();
    $logger = new NullLogger();
    $apiKey = 'AIzaSyDy_Xw1r0AruzSK_KJSQfLY-jvkah17ayg';
    
    $service = new GeminiService($httpClient, $logger, $apiKey);
    echo "GeminiService instantiated.\n";
    
    $imagePath = 'public/bundles/nelmioapidoc/logo.png';
    if (!file_exists($imagePath)) {
        die("Test image not found at $imagePath\n");
    }

    echo "Testing describeImage with $imagePath...\n";
    $description = $service->describeImage($imagePath, 'image/png');
    
    echo "Success! Description: " . $description . "\n";

} catch (\Throwable $t) {
    echo "Error: " . $t->getMessage() . "\n";
    echo "Class: " . get_class($t) . "\n";
    echo "File: " . $t->getFile() . "\n";
    echo "Line: " . $t->getLine() . "\n";
}
