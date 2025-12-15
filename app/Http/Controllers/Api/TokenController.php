<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TokenController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user?->current_organization_id) {
            abort(403, 'Current organization not set.');
        }

        $token = $user->createToken('api', ['*'])->plainTextToken;

        return response()->json([
            'token_id' => $user->tokens()->latest()->first()->id,
            'token' => $token,
        ], 201);
    }

    public function index(Request $request): JsonResource
    {
        $user = $request->user();

        return JsonResource::collection(
            $user->tokens()->latest()->get(['id', 'name', 'last_used_at', 'created_at'])
        );
    }

    public function destroy(Request $request, int $tokenId): JsonResponse
    {
        $user = $request->user();

        $token = $user->tokens()->whereKey($tokenId)->first();

        if (! $token) {
            abort(403);
        }

        $token->delete();

        return response()->json([], 204);
    }

    public function webIndex(Request $request): View
    {
        $user = $request->user();

        $tokens = $user
            ->tokens()
            ->latest()
            ->get(['id', 'name', 'last_used_at', 'created_at']);

        return view('tokens.index', [
            'tokens' => $tokens,
            'hasCurrentOrg' => (bool) $user->current_organization_id,
        ]);
    }

    public function webDestroy(Request $request, int $tokenId): RedirectResponse
    {
        $token = $request->user()->tokens()->whereKey($tokenId)->first();

        if (! $token) {
            abort(403);
        }

        $token->delete();

        return redirect()->route('tokens.index');
    }
}
