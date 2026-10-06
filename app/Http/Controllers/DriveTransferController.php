<?php

namespace App\Http\Controllers;

use App\Services\DriveTransferService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class DriveTransferController extends Controller
{
    public function __construct(private readonly DriveTransferService $transfers) {}

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'operation' => ['required', 'string', 'in:move,copy'],
            'paths' => ['required', 'array', 'min:1', 'max:'.DriveTransferService::MAX_ITEMS],
            'paths.*' => ['required', 'string', 'max:2048', 'distinct'],
            'destination' => ['nullable', 'string', 'max:2048'],
        ]);

        try {
            $operation = $validated['operation'];
            $items = $this->transfers->transfer(
                $request->user(),
                $validated['paths'],
                (string) ($validated['destination'] ?? ''),
                $operation,
            );
            $count = count($items);
            $verb = $operation === 'move' ? 'moved' : 'copied';

            return response()->json([
                'success' => true,
                'message' => $count === 1 ? "1 item was {$verb}." : "{$count} items were {$verb}.",
                'items' => $items,
            ]);
        } catch (HttpExceptionInterface $e) {
            throw $e;
        } catch (RuntimeException $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (Exception $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'The file operation could not be completed.',
            ], 500);
        }
    }
}
