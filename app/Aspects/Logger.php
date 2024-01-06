<?php

namespace App\Aspects;

use AhmadVoid\SimpleAOP\Aspect;
use Illuminate\Support\Facades\Log;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
class Logger implements Aspect
{

    // The constructor can accept parameters for the attribute
    public function __construct()
    {

    }
    public function executeBefore($request, $controller, $method)
    {
        Log::info('Request: ' . $request->fullUrl());
        Log::info('Controller: ' . get_class($controller));
        Log::info('Method: ' . $method);
    }

    public function executeAfter($request, $controller, $method, $response)
    {
        Log::info('Response: ' . $response->getContent());
    }

    public function executeException($request, $controller, $method, $exception)
    {
        Log::error($exception->getMessage());
    }
}
