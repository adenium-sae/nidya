<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if locale is provided in the request (header or query parameter)
        $locale = $request->header('Accept-Language') 
                    ?? $request->query('locale') 
                    ?? config('app.locale');

        // Extract the primary language code if it's in format like 'en-US'
        if (str_contains($locale, '-')) {
            $locale = explode('-', $locale)[0];
        }
        if (str_contains($locale, ',')) {
            $locale = explode(',', $locale)[0];
        }

        // Validate that the locale is supported
        $supportedLocales = ['en', 'es'];
        if (!in_array($locale, $supportedLocales)) {
            $locale = config('app.locale');
        }

        // Set the application locale
        App::setLocale($locale);

        return $next($request);
    }
}
