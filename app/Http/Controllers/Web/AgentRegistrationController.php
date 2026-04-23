<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AgentRegistrationController extends Controller
{
    public function create(): View
    {
        return view('agent.auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => null,
            'type' => User::AUTH_TYPE_EMAIL,
            'role' => User::ROLE_AGENT,
            'password' => Hash::make($validated['password']),
            'otp_code' => null,
            'otp_sent_at' => null,
        ]);

        return redirect()
            ->route('agent.login')
            ->with('status', 'Akun agent berhasil dibuat. Silakan login dengan akun Anda.');
    }
}
