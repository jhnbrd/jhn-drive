<?php

namespace App\Http\Controllers;

use App\Services\DriveSearchService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DriveSearchController extends Controller
{
    public function __construct(private readonly DriveSearchService $search) {}

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:1', 'max:255', 'regex:/\\S/'],
            'type' => ['nullable', 'string', 'in:all,folder,image,video,audio,document,archive'],
            'sort' => ['nullable', 'string', 'in:name,modified,size,type'],
            'direction' => ['nullable', 'string', 'in:asc,desc'],
        ]);

        try {
            $result = $this->search->search(
                $request->user(),
                trim($validated['q']),
                $validated['type'] ?? 'all',
                $validated['sort'] ?? 'name',
                $validated['direction'] ?? 'asc',
            );

            return response()->json([
                'success' => true,
                'query' => trim($validated['q']),
                'count' => count($result['items']),
                'limit' => DriveSearchService::MAX_RESULTS,
                ...$result,
            ]);
        } catch (Exception $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Search is temporarily unavailable.',
            ], 500);
        }
    }
}
