<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>{{ config('app.name', 'Kresek.in') }} - Portal</title>
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
            --success: #04845f;
            --panel-blue: #0b6aa7;
            --panel-deep: #064b82;
            --danger: #c52525;
            --shadow: 0 24px 70px rgba(16, 24, 40, .12);
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        * {
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            margin: 0;
            overflow: hidden;
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

        .auth-shell {
            min-height: 100dvh;
            height: 100dvh;
            display: grid;
            place-items: center;
            padding: clamp(16px, 4dvh, 36px) 24px;
        }

        .auth-card {
            width: min(1180px, 100%);
            height: min(700px, calc(100dvh - 32px));
            max-height: calc(100dvh - 32px);
            display: grid;
            grid-template-columns: minmax(360px, .95fr) minmax(420px, 1fr);
            overflow: hidden;
            border: 1px solid rgba(215, 220, 231, .76);
            border-radius: 18px;
            background: #ffffff;
            box-shadow: var(--shadow);
        }

        .login-panel {
            min-height: 0;
            height: 100%;
            display: flex;
            flex-direction: column;
            padding: clamp(34px, 6dvh, 56px) 64px clamp(18px, 3dvh, 28px);
        }

        .brand {
            display: inline-flex;
            align-items: center;
            gap: 14px;
            width: fit-content;
            color: var(--brand);
            font-size: 31px;
            font-weight: 800;
            letter-spacing: 0;
        }

        .brand img {
            width: 45px;
            height: 45px;
            flex: 0 0 auto;
        }

        .brand strong {
            color: var(--warning);
        }

        .login-copy {
            margin-top: clamp(34px, 7dvh, 58px);
        }

        .login-copy h1 {
            margin: 0 0 12px;
            font-size: clamp(34px, 4vw, 44px);
            line-height: 1.08;
            letter-spacing: 0;
        }

        .login-copy p {
            max-width: 440px;
            margin: 0;
            color: var(--muted);
            font-size: 18px;
            line-height: 1.55;
        }

        .role-tabs {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 8px;
            margin-top: clamp(18px, 3.2dvh, 28px);
            padding: 6px;
            border: 1px solid var(--line);
            border-radius: 14px;
            background: #f7f9fc;
        }

        .role-tabs a {
            min-height: 40px;
            display: grid;
            place-items: center;
            border-radius: 10px;
            color: #4b5568;
            font-size: 14px;
            font-weight: 700;
        }

        .role-tabs a[aria-current="true"] {
            color: #ffffff;
            background: var(--brand);
            box-shadow: 0 8px 18px rgba(17, 190, 200, .24);
        }

        .login-form {
            display: grid;
            gap: clamp(16px, 2.7dvh, 22px);
            margin-top: clamp(20px, 3.6dvh, 32px);
        }

        .form-row {
            display: grid;
            gap: 9px;
        }

        .form-meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }

        label {
            color: #42495a;
            font-size: 15px;
            font-weight: 800;
            letter-spacing: .02em;
        }

        .field {
            min-height: 56px;
            display: flex;
            align-items: center;
            gap: 14px;
            border: 1.5px solid #cbd1df;
            border-radius: 10px;
            background: var(--field);
            padding: 0 18px;
            transition: border-color .18s ease, box-shadow .18s ease, background .18s ease;
        }

        .field:focus-within {
            border-color: var(--brand);
            background: #ffffff;
            box-shadow: 0 0 0 4px rgba(17, 190, 200, .14);
        }

        .field-icon {
            width: 25px;
            height: 25px;
            flex: 0 0 auto;
        }

        .button-icon {
            width: 30px;
            height: 30px;
            flex: 0 0 auto;
        }

        .field input {
            width: 100%;
            min-width: 0;
            border: 0;
            outline: 0;
            color: var(--ink);
            background: transparent;
            font: inherit;
            font-size: 18px;
        }

        .field input::placeholder {
            color: #8a92a3;
        }

        .icon-button {
            width: 42px;
            height: 42px;
            display: inline-grid;
            place-items: center;
            margin-right: -8px;
            border: 0;
            border-radius: 10px;
            color: #767d8f;
            background: transparent;
            cursor: pointer;
        }

        .icon-button:hover,
        .icon-button:focus-visible {
            color: var(--brand-dark);
            background: var(--brand-soft);
            outline: 0;
        }

        .forgot-link {
            font-size: 15px;
            font-weight: 800;
        }

        .alert {
            border: 1px solid rgba(197, 37, 37, .25);
            border-radius: 12px;
            color: var(--danger);
            background: #fff1f1;
            padding: 13px 15px;
            font-weight: 700;
        }

        .submit-button {
            min-height: 56px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 14px;
            margin-top: 4px;
            border: 0;
            border-radius: 10px;
            color: #ffffff;
            background: var(--brand);
            box-shadow: 0 15px 28px rgba(17, 190, 200, .24);
            cursor: pointer;
            font-size: 20px;
            font-weight: 800;
            transition: background .18s ease, transform .18s ease, box-shadow .18s ease;
        }

        .submit-button:hover {
            background: var(--brand-dark);
            transform: translateY(-1px);
            box-shadow: 0 18px 30px rgba(7, 154, 167, .28);
        }

        .submit-button:focus-visible {
            outline: 4px solid rgba(17, 190, 200, .24);
            outline-offset: 3px;
        }

        .submit-button[aria-busy="true"] {
            cursor: wait;
            opacity: .72;
        }

        .register-copy {
            margin-top: clamp(24px, 4.5dvh, 38px);
            color: var(--muted);
            text-align: center;
            font-size: 17px;
        }

        .register-copy a {
            font-weight: 800;
        }

        .copyright {
            margin-top: auto;
            padding-top: clamp(18px, 3dvh, 30px);
            color: #9ba3b3;
            text-align: center;
            font-size: 15px;
            font-weight: 700;
            letter-spacing: .02em;
        }

        .story-panel {
            position: relative;
            min-height: 0;
            height: 100%;
            overflow: hidden;
            background:
                linear-gradient(90deg, rgba(245, 252, 255, .98) 0%, rgba(229, 244, 255, .86) 38%, rgba(9, 79, 128, .13) 100%),
                linear-gradient(135deg, #f5fbff 0%, #dff2ff 55%, #b7e4ef 100%);
        }

        .story-panel::before {
            content: "";
            position: absolute;
            inset: 0;
            background:
                linear-gradient(115deg, transparent 0 33%, rgba(255, 255, 255, .52) 33% 52%, transparent 52%),
                radial-gradient(circle at 90% 12%, rgba(255, 255, 255, .68), transparent 16%);
            pointer-events: none;
        }

        .story-content {
            position: relative;
            z-index: 1;
            width: 100%;
            height: 100%;
            min-height: 100%;
            padding: 0;
        }

        .story-hero-image {
            display: block;
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
        }

        .story-brand {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            color: #078bb5;
            font-size: 25px;
            font-weight: 900;
        }

        .story-brand img {
            width: 34px;
            height: 34px;
        }

        .story-title {
            max-width: 360px;
            margin: 38px 0 0;
            font-size: clamp(42px, 5vw, 58px);
            line-height: .98;
            letter-spacing: 0;
        }

        .story-title span {
            color: var(--brand-dark);
        }

        .story-title::after {
            content: "";
            display: block;
            width: 74px;
            height: 5px;
            margin-top: 18px;
            border-radius: 999px;
            background: var(--brand);
        }

        .story-subtitle {
            max-width: 300px;
            margin: 20px 0 0;
            color: #17415d;
            font-size: 17px;
            font-weight: 700;
            line-height: 1.45;
        }

        .story-subtitle strong {
            color: var(--brand-dark);
        }

        .commission-card {
            position: absolute;
            top: 68px;
            right: 28px;
            width: min(270px, 42%);
            display: grid;
            grid-template-columns: 1fr 58px;
            gap: 14px;
            align-items: center;
            border: 1px solid rgba(255, 255, 255, .72);
            border-radius: 16px;
            background: rgba(255, 255, 255, .78);
            box-shadow: 0 18px 36px rgba(38, 84, 121, .18);
            backdrop-filter: blur(12px);
            padding: 18px;
        }

        .commission-card span {
            display: block;
            color: #345068;
            font-size: 12px;
            font-weight: 900;
        }

        .commission-card strong {
            display: block;
            margin-top: 6px;
            color: #078bb5;
            font-size: 22px;
        }

        .commission-card small {
            display: block;
            margin-top: 6px;
            color: #607086;
            font-weight: 700;
        }

        .commission-card small b {
            color: var(--success);
        }

        .wallet {
            width: 58px;
            height: 58px;
            display: grid;
            place-items: center;
            border-radius: 50%;
            color: #ffffff;
            background: linear-gradient(135deg, #08b7be, #087a66);
        }

        .people-scene {
            position: absolute;
            right: 82px;
            bottom: 235px;
            width: 260px;
            height: 290px;
        }

        .person {
            position: absolute;
            bottom: 0;
            width: 128px;
            height: 238px;
        }

        .person.left {
            left: 0;
        }

        .person.right {
            right: 0;
        }

        .head {
            position: absolute;
            left: 36px;
            top: 12px;
            width: 68px;
            height: 72px;
            border-radius: 50%;
            background: #f1bd9e;
            box-shadow: inset -10px -4px 0 rgba(179, 98, 68, .18);
        }

        .person.left .head::before {
            content: "";
            position: absolute;
            inset: -14px -18px -3px -23px;
            border-radius: 48% 48% 42% 46%;
            background: #cbb7ad;
            z-index: -1;
        }

        .person.right .head::before {
            content: "";
            position: absolute;
            inset: -16px -20px 12px 18px;
            border-radius: 48%;
            background: #24384f;
            z-index: -1;
        }

        .body {
            position: absolute;
            left: 22px;
            bottom: 0;
            width: 98px;
            height: 166px;
            border-radius: 42px 42px 18px 18px;
            background: linear-gradient(160deg, #0b8cad, #075f8e);
        }

        .person.right .body {
            background: linear-gradient(160deg, #86bee2, #4f92c1);
        }

        .bag {
            position: absolute;
            left: 82px;
            bottom: 20px;
            width: 84px;
            height: 104px;
            border-radius: 12px 12px 18px 18px;
            background: linear-gradient(180deg, #16c7d3, #0a9fb0);
            box-shadow: 0 20px 36px rgba(5, 110, 132, .22);
        }

        .bag::before {
            content: "";
            position: absolute;
            left: 25px;
            top: -17px;
            width: 34px;
            height: 28px;
            border: 5px solid rgba(255, 255, 255, .74);
            border-bottom: 0;
            border-radius: 17px 17px 0 0;
        }

        .seller-sign {
            position: absolute;
            left: 18px;
            bottom: 244px;
            width: 112px;
            border-radius: 14px;
            color: #ffffff;
            background: linear-gradient(180deg, #06688c, #038b9f);
            box-shadow: 0 18px 34px rgba(14, 75, 106, .19);
            padding: 16px 14px;
            font-size: 15px;
            font-weight: 900;
            line-height: 1.25;
        }

        .seller-sign strong {
            color: var(--warning);
        }

        .coin {
            position: absolute;
            right: 44px;
            top: 214px;
            width: 74px;
            height: 74px;
            display: grid;
            place-items: center;
            border-radius: 50%;
            color: #ffffff;
            background: radial-gradient(circle at 28% 24%, #ffe7a0, #e5a51f 56%, #c88612);
            box-shadow: 0 16px 26px rgba(166, 105, 20, .26);
            font-size: 24px;
            font-weight: 900;
            transform: rotate(18deg);
        }

        .program-card {
            position: absolute;
            left: 0;
            right: 0;
            bottom: 0;
            min-height: 302px;
            border-radius: 18px 18px 0 0;
            background:
                linear-gradient(180deg, rgba(19, 88, 143, .77), rgba(0, 92, 152, .94)),
                linear-gradient(135deg, rgba(255, 255, 255, .16), rgba(255, 255, 255, 0));
            box-shadow: 0 -18px 40px rgba(4, 71, 118, .24);
            padding: 36px 44px;
            color: #ffffff;
            backdrop-filter: blur(13px);
        }

        .program-card span {
            display: block;
            color: rgba(255, 255, 255, .84);
            font-size: 19px;
            font-weight: 900;
            letter-spacing: .05em;
        }

        .program-card h2 {
            max-width: 600px;
            margin: 20px 0 0;
            font-size: clamp(26px, 3vw, 34px);
            line-height: 1.24;
            letter-spacing: 0;
        }

        .program-actions {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-top: 30px;
        }

        .secondary-button {
            min-height: 54px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid rgba(255, 255, 255, .54);
            border-radius: 12px;
            color: #ffffff;
            background: rgba(17, 190, 200, .86);
            padding: 0 28px;
            font-size: 18px;
            font-weight: 900;
        }

        .secondary-button:hover,
        .secondary-button:focus-visible {
            color: #ffffff;
            background: var(--brand-dark);
            text-decoration: none;
            outline: 3px solid rgba(255, 255, 255, .42);
            outline-offset: 2px;
        }

        @media (max-width: 1060px) {
            body {
                overflow: auto;
            }

            .auth-shell {
                height: auto;
            }

            .auth-card {
                grid-template-columns: 1fr;
                max-width: 680px;
                height: auto;
                max-height: none;
            }

            .login-panel {
                min-height: auto;
                padding: 46px 32px 28px;
            }

            .login-copy {
                margin-top: 42px;
            }

            .story-panel {
                min-height: 520px;
            }

            .people-scene {
                right: 48px;
                bottom: 190px;
                transform: scale(.82);
                transform-origin: right bottom;
            }

            .program-card {
                min-height: 230px;
            }
        }

        @media (max-width: 640px) {
            .auth-shell {
                display: block;
                padding: 0;
            }

            .auth-card {
                min-height: 100vh;
                border: 0;
                border-radius: 0;
                box-shadow: none;
            }

            .login-panel {
                padding: 34px 20px 26px;
            }

            .brand {
                font-size: 27px;
            }

            .brand img {
                width: 39px;
                height: 39px;
            }

            .login-copy {
                margin-top: 34px;
            }

            .login-copy p {
                font-size: 16px;
            }

            .role-tabs {
                grid-template-columns: 1fr;
            }

            .form-meta {
                align-items: flex-start;
                flex-direction: column;
                gap: 8px;
            }

            .field input {
                font-size: 16px;
            }

            .register-copy {
                margin-top: 30px;
                font-size: 15px;
            }

            .copyright {
                padding-top: 28px;
            }

            .story-panel {
                min-height: 390px;
            }

            .story-content {
                padding: 0;
            }

            .story-brand,
            .seller-sign,
            .commission-card,
            .coin,
            .people-scene {
                display: none;
            }

            .story-title {
                max-width: 300px;
                margin-top: 0;
                font-size: 38px;
            }

            .story-subtitle {
                max-width: 270px;
                font-size: 15px;
            }

            .program-card {
                min-height: 210px;
                padding: 26px 20px;
            }

            .program-card span {
                font-size: 14px;
            }

            .program-card h2 {
                font-size: 23px;
            }

            .secondary-button {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <main class="auth-shell">
        <section class="auth-card" aria-label="Portal masuk Kresek.in">
            <div class="login-panel">
                <a class="brand" href="{{ url('/') }}" aria-label="Kresek.in">
                    <img src="{{ asset('images/kresekin-bag-mark.svg') }}" alt="" aria-hidden="true">
                    <span>kresek.<strong>in</strong></span>
                </a>

                <div class="login-copy">
                    <h1>Masuk ke Portal Kresek.in</h1>
                    <p>Kelola UMKM, transaksi, produk, dan komisi dalam satu dashboard operasional.</p>
                </div>

                <nav class="role-tabs" aria-label="Pilihan akses portal">
                    <a href="{{ route('seller.login') }}" aria-current="true">Seller</a>
                    <a href="#agent-program">Agent</a>
                    <a href="#agent-program">Finance</a>
                </nav>

                <form method="POST" action="{{ route('seller.login.store') }}" class="login-form" id="login-form">
                    @csrf

                    @if ($errors->any())
                        <div class="alert" role="alert">
                            {{ $errors->first() }}
                        </div>
                    @endif

                    <div class="form-row">
                        <label for="email">Email</label>
                        <div class="field">
                            <img class="field-icon" src="{{ asset('images/icon-mail.svg') }}" alt="" aria-hidden="true">
                            <input id="email" name="email" type="email" value="{{ old('email') }}" placeholder="nama@email.com" autocomplete="email" required autofocus>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-meta">
                            <label for="password">Password</label>
                            <a class="forgot-link" href="{{ route('seller.login') }}">Lupa password?</a>
                        </div>
                        <div class="field">
                            <img class="field-icon" src="{{ asset('images/icon-lock.svg') }}" alt="" aria-hidden="true">
                            <input id="password" name="password" type="password" placeholder="Masukkan password" autocomplete="current-password" required>
                            <button class="icon-button" type="button" id="toggle-password" aria-label="Tampilkan password" aria-pressed="false">
                                <img class="field-icon" src="{{ asset('images/icon-eye.svg') }}" alt="" aria-hidden="true">
                            </button>
                        </div>
                    </div>

                    <button class="submit-button" type="submit" id="submit-button">
                        <span>Masuk</span>
                        <img class="button-icon" src="{{ asset('images/icon-arrow-right.svg') }}" alt="" aria-hidden="true">
                    </button>
                </form>

                <p class="register-copy">
                    Belum punya akun seller?
                    <a href="mailto:admin@kresek.in?subject=Pendaftaran%20Akun%20Seller%20Kresek.in">Hubungi administrator</a>
                </p>

                <p class="copyright">&copy; {{ now()->year }} Kresek.in. All rights reserved.</p>
            </div>
            <aside class="story-panel" id="agent-program" aria-label="Program agent Kresek.in">
                <div class="story-content">
                    <img class="story-hero-image" src="{{ asset('images/agent-program-hero.svg') }}" alt="Program Agent Kresek.in untuk seller UMKM">
                </div>
            </aside>
        </section>
    </main>

    <script>
        const passwordInput = document.getElementById('password');
        const togglePassword = document.getElementById('toggle-password');
        const loginForm = document.getElementById('login-form');
        const submitButton = document.getElementById('submit-button');

        togglePassword?.addEventListener('click', () => {
            const isHidden = passwordInput.type === 'password';
            passwordInput.type = isHidden ? 'text' : 'password';
            togglePassword.setAttribute('aria-pressed', String(isHidden));
            togglePassword.setAttribute('aria-label', isHidden ? 'Sembunyikan password' : 'Tampilkan password');
        });

        loginForm?.addEventListener('submit', () => {
            submitButton.setAttribute('aria-busy', 'true');
            submitButton.disabled = true;
            submitButton.querySelector('span').textContent = 'Memproses';
        });
    </script>
</body>
</html>
