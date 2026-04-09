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
    echo "GeminiService instantiated successfully.\n";
    
    // Try to call a method (will probably fail without a real file, but we check if it runs)
    // $service->describeImage('non-existent.jpg', 'image/jpeg');
} catch (\Throwable $t) {
    echo "Error: " . $t->getMessage() . "\n";
    echo "File: " . $t->getFile() . "\n";
    echo "Line: " . $t->getLine() . "\n";
}
