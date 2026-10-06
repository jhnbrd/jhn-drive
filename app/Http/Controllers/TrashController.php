<?php

namespace App\Http\Controllers;

use App\Models\TrashedItem;
use App\Services\TrashService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class TrashController extends Controller
{
    public function __construct(private readonly TrashService $trash) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'items' => $this->trash->items($user)
                ->map(fn (TrashedItem $item): array => $this->trash->serialize($user, $item))
                ->values(),
        ]);
    }

    public function restore(Request $request, int $id): JsonResponse
    {
        try {
            $user = $request->user();
            $item = TrashedItem::where('user_id', $user->id)->find($id);
            if (! $item) {
                abort(404);
            }
            $restored = $this->trash->restore($user, $item);

            return response()->json([
                'success' => true,
                'message' => $restored['collided']
                    ? "Restored as '{$restored['name']}' because the original name is in use."
                    : "'{$restored['name']}' was restored.",
                ...$restored,
            ]);
        } catch (HttpExceptionInterface $e) {
            throw $e;
        } catch (RuntimeException $e) {
            report($e);

            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        try {
            $user = $request->user();
            $item = TrashedItem::where('user_id', $user->id)->find($id);
            if (! $item) {
                abort(404);
            }
            $name = $item->original_name;
            $this->trash->permanentlyDelete($user, $item);

            return response()->json([
                'success' => true,
                'message' => "'{$name}' was permanently deleted.",
            ]);
        } catch (HttpExceptionInterface $e) {
            throw $e;
        } catch (RuntimeException $e) {
            report($e);

            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function empty(Request $request): JsonResponse
    {
        try {
            $count = $this->trash->empty($request->user());

            return response()->json([
                'success' => true,
                'message' => $count === 1 ? '1 item was permanently deleted.' : "{$count} items were permanently deleted.",
            ]);
        } catch (Exception $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Trash could not be emptied.',
            ], 500);
        }
    }
}
