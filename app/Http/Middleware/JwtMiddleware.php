<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

class JwtMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            // Verificar si el token es válido
            $user = JWTAuth::parseToken()->authenticate();
            
            if (!$user) {
                return response()->json([
                    'message' => 'Usuario no encontrado',
                    'statusCode' => 404
                ], 404);
            }
            
        } catch (TokenExpiredException $e) {
            return response()->json([
                'message' => 'Token expirado',
                'statusCode' => 401
            ], 401);
            
        } catch (TokenInvalidException $e) {
            return response()->json([
                'message' => 'Token inválido',
                'statusCode' => 401
            ], 401);
            
        } catch (JWTException $e) {
            return response()->json([
                'message' => 'Token no proporcionado',
                'statusCode' => 401
            ], 401);
        }

        return $next($request);
    }
}