<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;

class ApiDocsController extends Controller
{
    public function __invoke(): Response
    {
        $path = base_path('docs/api.md');

        abort_unless(File::exists($path), 404, 'API docs not found.');

        return response(File::get($path), 200, [
            'Content-Type' => 'text/markdown; charset=utf-8',
        ]);
    }
}
