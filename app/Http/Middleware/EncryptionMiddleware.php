<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class EncryptionMiddleware
{
    private const ENCRYPTION_METHOD = 'AES-256-CTR';
    
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $key = env('ENCRYPTION_KEY');
        $iv = env('ENCRYPTION_IV');

        // Skip encryption/decryption if keys are not configured
        if (!$key || !$iv) {
            Log::warning('Encryption Middleware - Encryption keys not configured');
            return $next($request);
        }

        // Validate key and IV length
        if (strlen($key) !== 32) {
            Log::error('Encryption Middleware - Invalid encryption key length');
            return $this->errorResponse('Encryption configuration error', 500);
        }

        if (strlen($iv) !== 16) {
            Log::error('Encryption Middleware - Invalid IV length');
            return $this->errorResponse('Encryption configuration error', 500);
        }

        // Decrypt incoming request for POST/PUT methods
        if ($request->isMethod('POST') || $request->isMethod('PUT') || $request->isMethod('PATCH')) {
            $decryptionResult = $this->decryptRequest($request, $key, $iv);
            
            if ($decryptionResult instanceof Response) {
                return $decryptionResult; // Return error response
            }
        }

        $response = $next($request);

        // Encrypt outgoing JSON responses
        return $this->encryptResponse($response, $key, $iv);
    }

    /**
     * Decrypt the incoming request payload.
     */
    private function decryptRequest(Request $request, string $key, string $iv): ?Response
    {
        $encryptedData = $this->extractEncryptedData($request);

        if (!$encryptedData) {
            Log::info('Encryption Middleware - No encrypted data field found in request');
            return null;
        }

        try {
            $decrypted = $this->decryptPayload($encryptedData, $key, $iv);
            
            if ($decrypted === null) {
                return $this->errorResponse('Failed to decrypt request payload', 400);
            }

            $jsonData = json_decode($decrypted, true, 512, JSON_THROW_ON_ERROR);
            
            if (!is_array($jsonData)) {
                Log::error('Encryption Middleware - Decrypted data is not valid JSON');
                return $this->errorResponse('Invalid request format', 400);
            }

            Log::info('Encryption Middleware - Request decrypted successfully', [
                'fields' => array_keys($jsonData)
            ]);

            $request->replace($jsonData);
            return null;

        } catch (\JsonException $e) {
            Log::error('Encryption Middleware - JSON decode failed', [
                'error' => $e->getMessage()
            ]);
            return $this->errorResponse('Invalid JSON format in encrypted payload', 400);
        } catch (\Exception $e) {
            Log::error('Encryption Middleware - Exception during decryption', [
                'error' => $e->getMessage()
            ]);
            return $this->errorResponse('Decryption failed', 400);
        }
    }

    /**
     * Extract encrypted data from request using multiple strategies.
     */
    private function extractEncryptedData(Request $request): ?string
    {
        // Strategy 1: Check 'data' parameter from form/JSON input
        $data = $request->input('data');
        if (!empty($data)) {
            return $data;
        }

        // Strategy 2: Check raw JSON payload for 'data' field
        if ($request->isJson()) {
            $rawContent = $request->getContent();
            if (!empty($rawContent)) {
                try {
                    $jsonData = json_decode($rawContent, true, 512, JSON_THROW_ON_ERROR);
                    return $jsonData['data'] ?? null;
                } catch (\JsonException $e) {
                    // Not valid JSON, continue to next strategy
                }
            }
        }

        // Strategy 3: For specific content types, consider the entire body
        $contentType = $request->header('Content-Type');
        $rawContent = $request->getContent();
        
        if (!empty($rawContent) && str_contains($contentType, 'text/plain')) {
            $trimmed = trim($rawContent, "\"'");
            if (!empty($trimmed)) {
                Log::info('Encryption Middleware - Using raw request body as encrypted payload');
                return $trimmed;
            }
        }

        return null;
    }

    /**
     * Decrypt the payload using OpenSSL.
     */
    private function decryptPayload(string $encryptedData, string $key, string $iv): ?string
    {
        $decoded = base64_decode($encryptedData, true);
        if ($decoded === false) {
            Log::error('Encryption Middleware - Base64 decode failed');
            return null;
        }

        $decrypted = openssl_decrypt(
            $decoded,
            self::ENCRYPTION_METHOD,
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );

        if ($decrypted === false) {
            Log::error('Encryption Middleware - Decryption failed', [
                'openssl_error' => openssl_error_string()
            ]);
            return null;
        }

        return $decrypted;
    }

    /**
     * Encrypt the response if it's a JSON response.
     */
    private function encryptResponse(Response $response, string $key, string $iv): Response
    {
        if (!$response instanceof JsonResponse) {
            return $response;
        }

        $content = $response->getData(true);
        
        // Don't encrypt empty responses or error responses that might not contain sensitive data
        if (empty($content) || $response->getStatusCode() >= 400) {
            return $response;
        }

        try {
            $jsonString = json_encode($content, JSON_THROW_ON_ERROR);
            
            $encrypted = openssl_encrypt(
                $jsonString,
                self::ENCRYPTION_METHOD,
                $key,
                OPENSSL_RAW_DATA,
                $iv
            );

            if ($encrypted === false) {
                Log::error('Encryption Middleware - Response encryption failed', [
                    'openssl_error' => openssl_error_string()
                ]);
                return $response; // Return original response if encryption fails
            }

            $response->setData(['data' => base64_encode($encrypted)]);
            
        } catch (\JsonException $e) {
            Log::error('Encryption Middleware - JSON encode failed during response encryption', [
                'error' => $e->getMessage()
            ]);
        } catch (\Exception $e) {
            Log::error('Encryption Middleware - Exception during response encryption', [
                'error' => $e->getMessage()
            ]);
        }

        return $response;
    }

    /**
     * Create a standardized error response.
     */
    private function errorResponse(string $message, int $code = 400): JsonResponse
    {
        return new JsonResponse([
            'error' => $message,
            'success' => false
        ], $code);
    }
}