<?php

use App\Kernel;
use App\Service\GeminiService;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

(new Dotenv())->bootEnv(dirname(__DIR__).'/.env');

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();

$container = $kernel->getContainer();
$geminiService = $container->get(GeminiService::class);

echo "GeminiService retrieved from container successfully.\n";

try {
    // We won't call a real method because we don't have a file, 
    // but just getting it confirms autowiring is OK.
    echo "Class: " . get_class($geminiService) . "\n";
} catch (\Throwable $t) {
    echo "Error: " . $t->getMessage() . "\n";
}
