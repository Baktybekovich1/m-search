<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\Exception\ClientException;
use Symfony\Component\HttpClient\Exception\ServerException;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Sends an image to Gemini Vision API and returns a descriptive text
 * that can be used for product search.
 */
class GeminiService
{
    // gemini-flash-latest: confirmed alias from check_models.php for v1beta
    private const API_URL = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-3-flash-preview:generateContent';
    private const MAX_IMAGE_SIZE = 512;
    private const MAX_RETRIES = 3;  // retry up to 3 times on 429

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        private readonly string $geminiApiKey
    ) {}

    /**
     * Analyzes the given image file and returns a search-friendly description.
     */
    public function describeImage(string $imagePath, string $mimeType): string
    {
        $imageData = $this->resizeImage($imagePath, $mimeType);
        if (empty($imageData)) {
            $this->logger->error('Failed to read or resize image file', ['path' => $imagePath]);
            throw new ServiceUnavailableHttpException(null, 'Failed to process image data.');
        }

        $imageBase64 = base64_encode($imageData);

        // If GD is available, resizeImage returns image/jpeg.
        // Otherwise it returns the original file contents.
        $actualMimeType = extension_loaded('gd') ? 'image/jpeg' : $mimeType;

        $body = [
            'contents' => [
                [
                    'parts' => [
                        [
                            'inline_data' => [
                                'mime_type' => $actualMimeType,
                                'data'      => $imageBase64,
                            ],
                        ],
                        [
                            'text' => 'Опиши этот товар кратко на русском языке: тип товара, цвет, материал, бренд (если видно). '
                                . 'Только текст, без оформления, 2-3 предложения.',
                        ],
                    ],
                ],
            ],
            'generationConfig' => [
                'maxOutputTokens' => 256,
                'temperature'     => 0.2,
            ],
        ];

        // Retry loop for rate limits
        $lastException = null;
        for ($attempt = 1; $attempt <= self::MAX_RETRIES; $attempt++) {
            try {
                $response = $this->httpClient->request(
                    'POST',
                    self::API_URL . '?key=' . $this->geminiApiKey,
                    [
                        'headers' => ['Content-Type' => 'application/json'],
                        'json'    => $body,
                    ]
                );

                $data = $response->toArray();
                return $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
            } catch (ClientException|ServerException $e) {
                $statusCode = $e->getResponse()->getStatusCode();
                $lastException = $e;
                $errorBody = $e->getResponse()->getContent(false);

                // Log the error for production debugging
                $this->logger->error('Gemini API error', [
                    'status_code' => $statusCode,
                    'error' => $errorBody,
                    'attempt' => $attempt
                ]);

                // Retry on 429 (Rate Limit) and 503 (Service Unavailable / High Demand)
                if (in_array($statusCode, [429, 503]) && $attempt < self::MAX_RETRIES) {
                    // Wait before retry: 5s, 10s, 15s...
                    sleep($attempt * 5);
                    continue;
                }

                if ($statusCode === 429) {
                    throw new TooManyRequestsHttpException(
                        60,
                        'Gemini API rate limit exceeded after ' . self::MAX_RETRIES . ' retries. '
                        . 'Details: ' . $errorBody
                    );
                }

                // Any other error — include the raw body
                throw new ServiceUnavailableHttpException(
                    null,
                    sprintf('Gemini API error (HTTP %d): %s', $statusCode, $errorBody)
                );
            } catch (TransportExceptionInterface $e) {
                $this->logger->error('Gemini transport error', [
                    'attempt' => $attempt,
                    'error' => $e->getMessage(),
                ]);

                if ($attempt < self::MAX_RETRIES) {
                    sleep($attempt * 2);
                    continue;
                }

                throw new ServiceUnavailableHttpException(
                    null,
                    'Gemini API is unreachable. Please try again later.'
                );
            }
        }

        throw new ServiceUnavailableHttpException(null, 'Gemini API failed after retries.');
    }

    /**
     * Resizes image to max MAX_IMAGE_SIZE px and returns JPEG binary.
     */
    private function resizeImage(string $imagePath, string $mimeType): string
    {
        if (!extension_loaded('gd')) {
            return file_get_contents($imagePath);
        }

        $src = match ($mimeType) {
            'image/png'  => imagecreatefrompng($imagePath),
            'image/gif'  => imagecreatefromgif($imagePath),
            'image/webp' => imagecreatefromwebp($imagePath),
            default      => imagecreatefromjpeg($imagePath),
        };

        if ($src === false) {
            return file_get_contents($imagePath);
        }

        $origW = imagesx($src);
        $origH = imagesy($src);
        $max   = self::MAX_IMAGE_SIZE;

        if ($origW <= $max && $origH <= $max) {
            ob_start();
            imagejpeg($src, null, 80);
            $output = ob_get_clean();
            imagedestroy($src);
            return $output;
        }

        if ($origW >= $origH) {
            $newW = $max;
            $newH = (int) round($origH * $max / $origW);
        } else {
            $newH = $max;
            $newW = (int) round($origW * $max / $origH);
        }

        $dst = imagecreatetruecolor($newW, $newH);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $origW, $origH);
        imagedestroy($src);

        ob_start();
        imagejpeg($dst, null, 80);
        $output = ob_get_clean();
        imagedestroy($dst);

        return $output;
    }
}
