<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Process;

class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'commit' => $this->commitHash(),
            'time' => now()->toIso8601String(),
        ]);
    }

    protected function commitHash(): string
    {
        $result = @Process::path(base_path())->run('git rev-parse --short HEAD');

        if ($result && $result->successful()) {
            return trim($result->output());
        }

        return 'unknown';
    }
}
