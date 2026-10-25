<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Sync\SyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SyncController extends Controller
{
    public function __construct(private readonly SyncService $sync) {}

    public function ping(): JsonResponse
    {
        return response()->json([
            'app' => 'gestao-financeira',
            'server_time' => $this->sync->now(),
        ]);
    }

    public function push(Request $request): JsonResponse
    {
        $data = $request->validate(['changes' => ['present', 'array']]);

        return response()->json([
            'stats' => $this->sync->apply($data['changes']),
            'server_time' => $this->sync->now(),
        ]);
    }

    public function pull(Request $request): JsonResponse
    {
        $serverTime = $this->sync->now();

        return response()->json([
            'changes' => $this->sync->changesSince($request->query('since')),
            'server_time' => $serverTime,
        ]);
    }
}
