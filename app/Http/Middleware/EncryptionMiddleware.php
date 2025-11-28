<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EncryptionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $key = env('ENCRYPTION_KEY');
        $iv = env('ENCRYPTION_IV');

        if (!$key || !$iv) {
            return $next($request);
        }

        // Decrypt Request
        if ($request->isMethod('POST') || $request->isMethod('PUT')) {
            $data = $request->input('data');
            if ($data) {
                try {
                    \Log::info('Encryption Middleware - Received encrypted data', [
                        'data_length' => strlen($data),
                        'data_preview' => substr($data, 0, 50)
                    ]);

                    $decoded = base64_decode($data, true);
if ($decoded === false) {
    \Log::error('Encryption Middleware - Base64 decode failed for request payload');
    $decrypted = false;
} else {
    $decrypted = openssl_decrypt(
        $decoded,
        'AES-256-CTR',
        $key,
        OPENSSL_RAW_DATA,
        $iv
    );
}

                    if ($decrypted === false) {
                        \Log::error('Encryption Middleware - Decryption failed', [
                            'openssl_error' => openssl_error_string()
                        ]);
                    } else {
                        \Log::info('Encryption Middleware - Decryption successful', [
                            'decrypted' => $decrypted
                        ]);

                        $json = json_decode($decrypted, true);
                        if ($json) {
                            \Log::info('Encryption Middleware - JSON decoded successfully', [
                                'fields' => array_keys($json)
                            ]);
                            $request->replace($json);
                        } else {
                            \Log::error('Encryption Middleware - JSON decode failed', [
                                'json_error' => json_last_error_msg()
                            ]);
                        }
                    }
                } catch (\Exception $e) {
                    \Log::error('Encryption Middleware - Exception during decryption', [
                        'error' => $e->getMessage()
                    ]);
                }
            } else {
                \Log::info('Encryption Middleware - No encrypted data field found in request');
            }
        }

        $response = $next($request);

        // Encrypt Response
        if ($response instanceof \Illuminate\Http\JsonResponse) {
            $content = $response->getData(true);
            if ($content) {
                try {
                    $jsonString = json_encode($content);
                    $encrypted = openssl_encrypt(
        $jsonString,
        'AES-256-CTR',
        $key,
        OPENSSL_RAW_DATA,
        $iv
    );

                    if ($encrypted !== false) {
                        $response->setData(['data' => base64_encode($encrypted)]);
                    }
                } catch (\Exception $e) {
                    // Handle encryption error or ignore
                }
            }
        }

        return $response;
    }
}
