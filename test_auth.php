<?php

require_once __DIR__ . '/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\HttpClient\HttpClient;

(new Dotenv())->load(__DIR__ . '/.env');

$client = HttpClient::create();
$baseUrl = 'http://localhost:8000/api';

// 1. Get a user
$kernel = new App\Kernel('dev', true);
$kernel->boot();
$em = $kernel->getContainer()->get('doctrine.orm.entity_manager');
$user = $em->getRepository(App\Entity\User::class)->findOneBy([]);

if (!$user) {
    echo "No user for testing. Run seeding first.\n";
    exit(1);
}

$email = $user->getEmail();
echo "Testing forgot password for: $email\n";

// 2. Request forgot password
$response = $client->request('POST', "$baseUrl/auth/forgot-password", [
    'json' => ['email' => $email]
]);

if ($response->getStatusCode() !== 200) {
    echo "Forgot password request failed: " . $response->getContent(false) . "\n";
    exit(1);
}
echo "Forgot password requested.\n";

// 3. Read token from DB
$em->clear();
$user = $em->getRepository(App\Entity\User::class)->findOneBy(['email' => $email]);
$token = $user->getResetToken();
echo "Token from DB: $token\n";

// 4. Reset password
$newPass = 'new_password_123';
$response = $client->request('POST', "$baseUrl/auth/reset-password", [
    'json' => [
        'email' => $email,
        'token' => $token,
        'password' => $newPass
    ]
]);

if ($response->getStatusCode() !== 200) {
    echo "Reset password failed: " . $response->getContent(false) . "\n";
    exit(1);
}
echo "Password reset successful.\n";

// 5. Try to login with new password
$response = $client->request('POST', "$baseUrl/auth/login", [
    'json' => [
        'username' => $email,
        'password' => $newPass
    ]
]);

if ($response->getStatusCode() !== 200) {
    echo "Login with NEW password failed: " . $response->getContent(false) . "\n";
    exit(1);
}
echo "Login with NEW password successful! [TOKEN: " . substr($response->toArray()['token'], 0, 20) . "...]\n";
echo "SUCCESS!\n";
