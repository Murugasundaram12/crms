<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        // Show the login page.
        return view('pages.auth.login');
    }

    public function login(Request $request)
    {
        // Validate the login form before trying to sign the user in.
        $validatedCredentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // Attempt to log the user in with the validated credentials.
        $rememberUser = $request->boolean('remember');
        $loginWasSuccessful = Auth::attempt($validatedCredentials, $rememberUser);

        if ($loginWasSuccessful) {
            // Regenerate the session to prevent session fixation issues.
            $request->session()->regenerate();

            return redirect()->intended('/dashboard');
        }

        // Show a validation message when the credentials are invalid.
        throw ValidationException::withMessages([
            'email' => ['The provided credentials do not match our records.'],
        ]);
    }

    public function showRegister()
    {
        // Show the registration page.
        return view('pages.auth.register');
    }

    public function register(Request $request)
    {
        // Validate the registration form.
        $validatedData = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        // Create the user account with a hashed password.
        $user = User::create([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'role' => 'Employee',
            'password' => Hash::make($validatedData['password']),
        ]);

        // Attach the default Employee role when it exists.
        $employeeRole = Role::where('name', 'Employee')->first();

        if ($employeeRole) {
            $user->roles()->syncWithoutDetaching([$employeeRole->id]);
        }

        // Log the newly registered user in immediately.
        Auth::login($user);

        return redirect()->route('dashboard');
    }

    public function logout(Request $request)
    {
        // Log the current user out and clear the existing session.
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
