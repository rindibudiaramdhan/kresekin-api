<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterAgentWebRequest;
use App\Models\User;
use App\Notifications\RegistrationOtpNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AgentRegistrationController extends Controller
{
    public function create(): View
    {
        return view('agent.register');
    }

    public function store(RegisterAgentWebRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $otp = (string) random_int(100000, 999999);

        $user = DB::transaction(function () use ($request, $validated, $otp): User {
            $user = User::query()->create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'type' => User::AUTH_TYPE_EMAIL,
                'role' => User::ROLE_AGENT,
                'agent_code' => User::generateAgentCode(),
                'password' => null,
                'terms_accepted_at' => now(),
                'terms_version' => User::AGENT_REGISTRATION_TERMS_VERSION,
                'privacy_accepted_at' => now(),
                'agent_verification_status' => User::AGENT_VERIFICATION_PENDING_REVIEW,
                'otp_code' => Hash::make($otp),
                'otp_sent_at' => now(),
            ]);

            $identityDocumentPath = $request
                ->file('identity_document')
                ->storeAs(
                    "agent-identities/{$user->id}",
                    'document.'.$request->file('identity_document')->extension(),
                    'local'
                );

            $user->forceFill([
                'identity_document_path' => $identityDocumentPath,
            ])->save();

            return $user;
        });

        $user->notify(new RegistrationOtpNotification($otp));

        return redirect()
            ->route('agent.register.verify-otp', ['email' => $user->email])
            ->with('status', 'Registrasi berhasil. Kami telah mengirim OTP untuk verifikasi akun agent Anda.');
    }

    public function verifyOtp(): View
    {
        return view('agent.verify-otp', [
            'email' => request()->query('email'),
        ]);
    }
}
