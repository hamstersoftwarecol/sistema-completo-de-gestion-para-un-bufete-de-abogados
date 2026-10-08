<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;

/**
 * Sirve el logotipo del bufete sin depender de "storage:link" (útil en XAMPP/Windows).
 */
class BrandingController extends Controller
{
    public function logo()
    {
        $path = setting('logo_path');

        if ($path && Storage::disk('local')->exists($path)) {
            return response()->file(Storage::disk('local')->path($path), [
                'Cache-Control' => 'public, max-age=3600',
                'X-Content-Type-Options' => 'nosniff',
            ]);
        }

        return response(file_get_contents(resource_path('images/logo-default.svg')), 200, [
            'Content-Type' => 'image/svg+xml',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
