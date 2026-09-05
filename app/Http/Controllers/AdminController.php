<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;

class AdminController extends Controller
{
    /**
     * Show admin dashboard with pending access requests and user quotas.
     */
    public function index(): View
    {
        $pendingUsers = User::where('status', 'pending')->latest()->get();
        $approvedUsers = User::where('status', 'approved')->withCount('sharedLinks')->latest()->get();
        $rejectedUsers = User::where('status', 'rejected')->latest()->get();

        // Calculate total storage consumed across all approved users
        $totalUsedBytes = 0;
        foreach ($approvedUsers as $user) {
            $totalUsedBytes += $user->usedStorageBytes();
        }

        $metrics = [
            'total_users' => User::count(),
            'approved_count' => $approvedUsers->count(),
            'pending_count' => $pendingUsers->count(),
            'total_used_human' => User::formatBytes($totalUsedBytes),
        ];

        return view('admin.index', compact('pendingUsers', 'approvedUsers', 'rejectedUsers', 'metrics'));
    }

    /**
     * Approve a pending access request.
     */
    public function approve(int $id): RedirectResponse
    {
        $user = User::findOrFail($id);
        $user->update([
            'status' => 'approved',
            'approved_at' => now(),
        ]);

        $user->ensureStorageDirectoryExists();

        return back()->with('success', "Account for '{$user->name}' ({$user->email}) was approved. They can now log in with 20GB storage.");
    }

    /**
     * Reject a pending access request.
     */
    public function reject(int $id): RedirectResponse
    {
        $user = User::findOrFail($id);
        $user->update([
            'status' => 'rejected',
        ]);

        return back()->with('info', "Registration request for '{$user->name}' was declined.");
    }

    /**
     * Delete a user account and their storage folder.
     */
    public function deleteUser(Request $request, int $id): RedirectResponse
    {
        $user = User::findOrFail($id);

        if ($user->id === $request->user()->id) {
            return back()->with('error', 'You cannot delete your own superadmin account.');
        }

        // Delete user's storage folder
        $userFolder = $user->storageFullPath();
        if (is_dir($userFolder)) {
            File::deleteDirectory($userFolder);
        }

        $userName = $user->name;
        $user->delete();

        return back()->with('success', "User '{$userName}' and all associated files were permanently deleted.");
    }
}
