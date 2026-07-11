<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class CheckInstalled
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            if (!Schema::hasTable('gifs')) {
                Artisan::call('migrate', [
                    '--force' => true,
                    '--quiet' => true,
                ]);
            }
        } catch (\Exception $e) {
            return response()->view('pages.setup', [
                'error' => $e->getMessage(),
            ]);
        }

        return $next($request);
    }
}
