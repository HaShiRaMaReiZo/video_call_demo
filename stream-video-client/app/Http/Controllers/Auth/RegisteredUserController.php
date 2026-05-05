<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\PhoneDigits;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:32',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'locale' => 'required|string|in:en,es',
        ]);

        $phoneDigits = PhoneDigits::normalize($request->string('phone')->toString());
        if (strlen($phoneDigits) < 10 || strlen($phoneDigits) > 15) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'phone' => __('Use a valid phone number (10–15 digits).'),
            ]);
        }

        if (User::query()->where('phone', $phoneDigits)->exists()) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'phone' => __('The phone number is already registered.'),
            ]);
        }

        $user = User::create([
            'name' => $request->input('name'),
            'phone' => $phoneDigits,
            'email' => null,
            'password' => $request->string('password')->toString(),
            'locale' => $request->input('locale'),
            'stream_user_id' => (string) Str::uuid(),
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
