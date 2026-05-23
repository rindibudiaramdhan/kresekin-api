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
        if ($request->filled('phone')) {
            $phone = preg_replace('/\s+/', '', (string) $request->input('phone')) ?? '';

            if (str_starts_with($phone, '0')) {
                $phone = '+62'.substr($phone, 1);
            }

            $request->merge([
                'phone' => $phone,
            ]);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'phone' => ['required', 'string', 'max:20', 'regex:/^\+?[0-9]{8,15}$/', Rule::unique('users', 'phone')],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'terms' => ['accepted'],
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'type' => User::AUTH_TYPE_EMAIL,
            'role' => User::ROLE_AGENT,
            'agent_code' => User::generateAgentCode(),
            'password' => Hash::make($validated['password']),
            'otp_code' => null,
            'otp_sent_at' => null,
        ]);

        return redirect()
            ->route('agent.login')
            ->with('status', 'Akun agent berhasil dibuat. Silakan login dengan akun Anda.');
    }
}
