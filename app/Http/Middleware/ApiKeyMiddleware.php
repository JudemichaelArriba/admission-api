<?php
// app/Http/Middleware/ApiKeyMiddleware.php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class ApiKeyMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $rawKey = $request->header('API-Key');

        if (!$rawKey) {
            return response()->json(['message' => 'Missing API key.'], 401);
        }


        $apiKey = Cache::remember("api_key:{$rawKey}", 300, function () use ($rawKey) {
            return ApiKey::where('key', $rawKey)->first();
        });

        if (!$apiKey || !$apiKey->isValid()) {
            return response()->json(['message' => 'Invalid or revoked API key.'], 401);
        }


        ApiKey::where('id', $apiKey->id)->update([
            'last_used_at' => now(),
            'last_used_ip' => $request->ip(),
        ]);


        $request->attributes->set('api_client', $apiKey->client_name);

        return $next($request);
    }
}