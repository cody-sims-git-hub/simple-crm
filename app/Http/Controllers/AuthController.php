<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\DemoData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    // Show the login form.
    public function showLogin()
    {
        return view('auth.login');
    }

    // Authenticate an existing user.
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => 'These credentials do not match our records.',
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    // Show the registration form.
    public function showRegister()
    {
        return view('auth.register');
    }

    // Register a new user and log them in.
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        // Populate the new account with the starter pipeline.
        DemoData::provisionLeadsFor($user);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }

    // Show the "forgot password" request form.
    public function showForgotPassword()
    {
        return view('auth.forgot-password');
    }

    // Email a password reset link. Always responds neutrally so the form can't
    // be used to discover which emails are registered, and never resets the
    // shared read-only demo account.
    public function sendResetLink(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $neutral = 'If that email is registered, we have sent a password reset link.';

        if ($request->input('email') !== config('demo.email')) {
            Password::sendResetLink($request->only('email'));
        }

        return back()->with('status', $neutral);
    }

    // Show the form to choose a new password (reached via the emailed link).
    public function showResetPassword(Request $request, string $token)
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->input('email'),
        ]);
    }

    // Persist the new password for a valid token.
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill(['password' => Hash::make($password)])->save();
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        return redirect()->route('login')->with('status', 'Your password has been reset. Please sign in.');
    }

    // Log the user out.
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
