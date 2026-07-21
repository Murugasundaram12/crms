<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use App\Services\SingleLoginService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
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
        //dd($request->all());
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
            app(SingleLoginService::class)->invalidateOtherLogins(
                (int) Auth::id(),
                $request->session()->getId()
            );

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

    public function showForgotPasswordForm()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('pages.auth.forgot-password');
    }

    public function sendResetLinkEmail(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $status = Password::sendResetLink($request->only('email'));

        if ($status === Password::RESET_LINK_SENT) {
            return back()->with('status', __($status));
        }

        throw ValidationException::withMessages([
            'email' => [__($status)],
        ]);
    }

    public function showResetPasswordForm(Request $request, string $token)
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('pages.auth.reset-password', [
            'email' => $request->query('email'),
            'token' => $token,
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')->with('status', __($status));
        }

        throw ValidationException::withMessages([
            'email' => [__($status)],
        ]);
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
        $request->session()->regenerate();
        app(SingleLoginService::class)->invalidateOtherLogins(
            (int) $user->id,
            $request->session()->getId()
        );

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
