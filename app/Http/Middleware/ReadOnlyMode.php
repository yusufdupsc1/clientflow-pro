<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ReadOnlyMode
{
    public function handle(Request $request, Closure $next): Response
    {
        if (config('app.read_only', false) === true && $this->isWriteMethod($request)) {
            $message = 'The application is in read-only mode.';

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 503);
            }

            abort(503, $message);
        }

        return $next($request);
    }

    protected function isWriteMethod(Request $request): bool
    {
        return ! in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true);
    }
}
