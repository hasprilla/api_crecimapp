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
                    $decrypted = openssl_decrypt(
                        $data,
                        'AES-256-CTR',
                        $key,
                        0,
                        $iv
                    );

                    if ($decrypted) {
                        $json = json_decode($decrypted, true);
                        if ($json) {
                            $request->replace($json);
                        }
                    }
                } catch (\Exception $e) {
                    // Handle decryption error or ignore
                }
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
                        0,
                        $iv
                    );

                    if ($encrypted) {
                        $response->setData(['data' => $encrypted]);
                    }
                } catch (\Exception $e) {
                    // Handle encryption error or ignore
                }
            }
        }

        return $response;
    }
}
