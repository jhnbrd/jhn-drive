<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AuthController extends Controller
{
    /**
     * Show the login form.
     */
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('drive.index');
        }

        return view('auth.login');
    }

    /**
     * Handle user login.
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return back()->withErrors([
                'email' => 'The provided credentials do not match our records.',
            ])->onlyInput('email');
        }

        // Check approval status
        if ($user->isPending()) {
            return back()->withErrors([
                'email' => 'Your access request is currently pending review by the superadmin. You will be able to sign in once approved.',
            ])->onlyInput('email');
        }

        if ($user->isRejected()) {
            return back()->withErrors([
                'email' => 'Your access request was declined by the administrator. Please contact support.',
            ])->onlyInput('email');
        }

        // Log the user in
        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        // Ensure user storage folder exists
        $user->ensureStorageDirectoryExists();

        return redirect()->intended(route('drive.index'));
    }

    /**
     * Show the access request form (replaces public registration).
     */
    public function showRequestAccess(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('drive.index');
        }

        return view('auth.request-access');
    }

    /**
     * Handle registration access request submission.
     */
    public function submitRequestAccess(Request $request): View|RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'request_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'is_superadmin' => false,
            'status' => 'pending',
            'request_note' => $validated['request_note'] ?? null,
        ]);

        return view('auth.request-access', [
            'submitted' => true,
            'applicant' => $user,
        ]);
    }

    /**
     * Handle user logout.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
