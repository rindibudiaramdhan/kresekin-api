<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>Verifikasi OTP Agent - {{ config('app.name', 'Kresek.in') }}</title>
    <style>
        :root {
            --brand: #11bec8;
            --brand-dark: #079aa7;
            --brand-soft: #e7fbfd;
            --ink: #151922;
            --muted: #596070;
            --line: #d7dce7;
            --field: #f7f8fb;
            --warning: #ffb91f;
            --danger: #c52525;
            --success: #04845f;
            --shadow: 0 24px 70px rgba(16, 24, 40, .12);
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        * {
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            margin: 0;
            color: var(--ink);
            background:
                radial-gradient(circle at 16% 12%, rgba(17, 190, 200, .12), transparent 30%),
                linear-gradient(135deg, #ffffff 0%, #f7fafc 54%, #edf8fa 100%);
        }

        a {
            color: var(--brand-dark);
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }

        .page-shell {
            min-height: 100dvh;
            display: grid;
            place-items: center;
            padding: 32px 20px;
        }

        .otp-card {
            width: min(520px, 100%);
            border: 1px solid rgba(215, 220, 231, .82);
            border-radius: 18px;
            background: #ffffff;
            box-shadow: var(--shadow);
            padding: clamp(26px, 5vw, 44px);
        }

        .brand {
            display: inline-flex;
            align-items: center;
            gap: 13px;
            color: var(--brand);
            font-size: clamp(25px, 4vw, 31px);
            font-weight: 900;
            letter-spacing: 0;
        }

        .brand img {
            width: 43px;
            height: 43px;
            flex: 0 0 auto;
        }

        .brand strong {
            color: var(--warning);
        }

        .copy {
            margin-top: 28px;
        }

        .copy h1 {
            margin: 0;
            font-size: clamp(30px, 6vw, 40px);
            line-height: 1.08;
            letter-spacing: 0;
        }

        .copy p {
            margin: 12px 0 0;
            color: var(--muted);
            font-size: 16px;
            line-height: 1.55;
        }

        .copy strong {
            color: #24324a;
        }

        .status-message {
            display: none;
            margin-top: 18px;
            border-radius: 12px;
            padding: 12px 14px;
            font-size: 14px;
            font-weight: 800;
            line-height: 1.45;
        }

        .status-message.is-visible {
            display: block;
        }

        .status-message.success {
            color: #066b50;
            background: #e8f8f1;
            border: 1px solid rgba(4, 132, 95, .18);
        }

        .status-message.error {
            color: var(--danger);
            background: #fff1f1;
            border: 1px solid rgba(197, 37, 37, .2);
        }

        .otp-form {
            display: grid;
            gap: 18px;
            margin-top: 24px;
        }

        label {
            color: #42495a;
            font-size: 14px;
            font-weight: 800;
            letter-spacing: .01em;
        }

        .field {
            min-height: 58px;
            display: flex;
            align-items: center;
            border: 1.5px solid #cbd1df;
            border-radius: 10px;
            background: var(--field);
            padding: 0 16px;
            transition: border-color .18s ease, box-shadow .18s ease, background .18s ease;
        }

        .field:focus-within {
            border-color: var(--brand);
            background: #ffffff;
            box-shadow: 0 0 0 4px rgba(17, 190, 200, .14);
        }

        .field input {
            width: 100%;
            min-width: 0;
            border: 0;
            outline: 0;
            color: var(--ink);
            background: transparent;
            text-align: center;
            font: inherit;
            font-size: 24px;
            font-weight: 900;
            letter-spacing: .22em;
        }

        .form-help {
            margin: -8px 0 0;
            color: var(--muted);
            font-size: 14px;
            line-height: 1.45;
        }

        .actions {
            display: grid;
            gap: 12px;
            margin-top: 4px;
        }

        .primary-button,
        .secondary-button {
            min-height: 52px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            padding: 0 22px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 900;
        }

        .primary-button {
            border: 0;
            color: #ffffff;
            background: var(--brand);
            box-shadow: 0 15px 28px rgba(17, 190, 200, .24);
        }

        .primary-button:hover,
        .primary-button:focus-visible {
            background: var(--brand-dark);
            outline: 4px solid rgba(17, 190, 200, .22);
            outline-offset: 2px;
        }

        .primary-button[aria-busy="true"] {
            cursor: wait;
            opacity: .72;
        }

        .secondary-button {
            border: 1px solid var(--line);
            color: #4b5568;
            background: #ffffff;
        }

        .secondary-button:hover,
        .secondary-button:focus-visible {
            color: var(--brand-dark);
            border-color: rgba(17, 190, 200, .45);
            background: var(--brand-soft);
            text-decoration: none;
            outline: 0;
        }

        .copyright {
            margin: 26px 0 0;
            color: #9ba3b3;
            text-align: center;
            font-size: 14px;
            font-weight: 700;
        }

        @media (max-width: 560px) {
            .page-shell {
                display: block;
                padding: 0;
            }

            .otp-card {
                min-height: 100vh;
                border: 0;
                border-radius: 0;
                box-shadow: none;
            }
        }
    </style>
</head>
<body>
    <main class="page-shell">
        <section class="otp-card" aria-label="Verifikasi OTP agent Kresek.in">
            <a class="brand" href="{{ url('/') }}" aria-label="Kresek.in">
                <img src="{{ asset('images/kresekin-bag-mark.svg') }}" alt="" aria-hidden="true">
                <span>Kresek.<strong>in</strong> Agent</span>
            </a>

            <div class="copy">
                <h1>Verifikasi OTP</h1>
                <p>
                    OTP registrasi dikirim ke
                    <strong id="email-copy">{{ $email ?: 'email agent Anda' }}</strong>.
                </p>
            </div>

            <div
                class="status-message {{ session('status') ? 'is-visible success' : '' }}"
                id="otp-status"
                role="status"
                aria-live="polite"
            >{{ session('status') }}</div>

            <form class="otp-form" id="otp-form" novalidate>
                <input type="hidden" name="role" value="agent">
                <input type="hidden" name="type" value="email">
                <input type="hidden" name="email" value="{{ $email }}">

                <label for="otp">Kode OTP</label>
                <div class="field">
                    <input id="otp" name="otp" type="text" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" placeholder="000000" autocomplete="one-time-code" required autofocus>
                </div>
                <p class="form-help">Masukkan 6 digit OTP dari email registrasi.</p>

                <div class="actions">
                    <button class="primary-button" type="submit" id="submit-button">Verifikasi OTP</button>
                    <a class="secondary-button" href="{{ route('agent.register') }}">Kembali ke Register</a>
                </div>
            </form>

            <p class="copyright">&copy; {{ now()->year }} Kresek.in. All rights reserved.</p>
        </section>
    </main>

    <script>
        const otpForm = document.getElementById('otp-form');
        const submitButton = document.getElementById('submit-button');
        const statusMessage = document.getElementById('otp-status');
        const otpInput = document.getElementById('otp');

        function setStatus(message = '', type = '') {
            statusMessage.textContent = message;
            statusMessage.className = 'status-message';

            if (message && type) {
                statusMessage.classList.add('is-visible', type);
            }
        }

        otpForm?.addEventListener('submit', async (event) => {
            event.preventDefault();

            if (!otpForm.reportValidity()) {
                return;
            }

            submitButton.disabled = true;
            submitButton.setAttribute('aria-busy', 'true');
            submitButton.textContent = 'Memverifikasi OTP';
            setStatus();

            try {
                const response = await fetch('{{ url('/api/users/verify-otp') }}', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                    },
                    body: new FormData(otpForm),
                });

                const payload = await response.json().catch(() => ({}));

                if (!response.ok) {
                    throw new Error(payload.message || 'Kode OTP tidak valid.');
                }

                const token = payload.data?.token;

                if (token) {
                    localStorage.setItem('kresekin_token', token);
                    localStorage.setItem('kresekin_token_type', payload.data?.token_type ?? 'Bearer');
                    localStorage.setItem('kresekin_user_role', payload.data?.user?.role ?? 'agent');
                }

                setStatus(payload.message || 'OTP berhasil diverifikasi.', 'success');
                otpInput.value = '';
                window.location.assign('{{ route('agent.dashboard') }}');
            } catch (error) {
                setStatus(error.message, 'error');
            } finally {
                submitButton.disabled = false;
                submitButton.removeAttribute('aria-busy');
                submitButton.textContent = 'Verifikasi OTP';
            }
        });
    </script>
</body>
</html>
