<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>Daftar Agent - {{ config('app.name', 'Kresek.in') }}</title>
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
                radial-gradient(circle at 14% 10%, rgba(17, 190, 200, .12), transparent 30%),
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

        .register-card {
            width: min(720px, 100%);
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

        .hero-copy {
            margin-top: 24px;
        }

        .hero-copy h1 {
            margin: 0;
            font-size: clamp(30px, 6vw, 42px);
            line-height: 1.08;
            letter-spacing: 0;
        }

        .hero-copy p {
            margin: 12px 0 0;
            color: var(--muted);
            font-size: 17px;
            line-height: 1.55;
        }

        .section-title {
            margin: 30px 0 0;
        }

        .section-title h2 {
            margin: 0;
            font-size: 23px;
            letter-spacing: 0;
        }

        .section-title p {
            margin: 7px 0 0;
            color: var(--muted);
            line-height: 1.5;
        }

        .status-message {
            margin-top: 18px;
            border: 1px solid rgba(4, 132, 95, .18);
            border-radius: 12px;
            color: #066b50;
            background: #e8f8f1;
            padding: 12px 14px;
            font-size: 14px;
            font-weight: 800;
            line-height: 1.45;
        }

        .error-summary {
            margin-top: 18px;
            border: 1px solid rgba(197, 37, 37, .2);
            border-radius: 12px;
            color: var(--danger);
            background: #fff1f1;
            padding: 12px 14px;
            font-size: 14px;
            font-weight: 800;
            line-height: 1.45;
        }

        .register-form {
            display: grid;
            gap: 18px;
            margin-top: 24px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
        }

        .form-row {
            display: grid;
            gap: 8px;
        }

        .form-row.full {
            grid-column: 1 / -1;
        }

        .form-row.compact-field {
            gap: 6px;
        }

        .form-row.compact-field label {
            color: #3f4656;
            font-size: 12px;
            font-weight: 900;
            letter-spacing: .06em;
        }

        .form-row.compact-field .field {
            min-height: 46px;
            border-radius: 7px;
            background: #ffffff;
            padding: 0 14px;
        }

        .form-row.compact-field .field input {
            font-size: 15px;
        }

        .form-row.compact-field .field input::placeholder {
            color: #a7adba;
        }

        label,
        .field-label {
            color: #42495a;
            font-size: 14px;
            font-weight: 800;
            letter-spacing: .01em;
        }

        .required {
            color: var(--danger);
        }

        .field {
            min-height: 52px;
            display: flex;
            align-items: center;
            border: 1.5px solid #cbd1df;
            border-radius: 10px;
            background: var(--field);
            padding: 0 16px;
            transition: border-color .18s ease, box-shadow .18s ease, background .18s ease;
        }

        .field:focus-within,
        .upload-field:focus-within {
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
            font: inherit;
            font-size: 16px;
        }

        .field input::placeholder {
            color: #8a92a3;
        }

        .upload-field {
            min-height: 132px;
            display: grid;
            place-items: center;
            border: 1.5px dashed #aeb7c8;
            border-radius: 12px;
            background: #f8fbfd;
            padding: 20px;
            text-align: center;
            transition: border-color .18s ease, box-shadow .18s ease, background .18s ease;
        }

        .upload-field input {
            width: min(360px, 100%);
            margin-top: 14px;
            color: var(--muted);
            font: inherit;
            font-size: 14px;
        }

        .upload-title {
            display: block;
            color: #24324a;
            font-size: 16px;
            font-weight: 900;
        }

        .upload-help,
        .field-help {
            margin: 4px 0 0;
            color: var(--muted);
            font-size: 13px;
            line-height: 1.45;
        }

        .checkbox-row {
            display: flex;
            align-items: flex-start;
            gap: 11px;
            border: 1px solid rgba(17, 190, 200, .18);
            border-radius: 12px;
            background: var(--brand-soft);
            padding: 14px;
        }

        .checkbox-row input {
            width: 18px;
            height: 18px;
            margin-top: 2px;
            accent-color: var(--brand);
            flex: 0 0 auto;
        }

        .checkbox-row label {
            font-size: 14px;
            line-height: 1.5;
        }

        .field-error {
            color: var(--danger);
            font-size: 13px;
            font-weight: 800;
            line-height: 1.35;
        }

        .actions {
            display: flex;
            justify-content: space-between;
            gap: 14px;
            margin-top: 6px;
        }

        .secondary-button,
        .primary-button {
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

        .copyright {
            margin: 26px 0 0;
            color: #9ba3b3;
            text-align: center;
            font-size: 14px;
            font-weight: 700;
        }

        @media (max-width: 680px) {
            .page-shell {
                display: block;
                padding: 0;
            }

            .register-card {
                min-height: 100vh;
                border: 0;
                border-radius: 0;
                box-shadow: none;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .actions {
                flex-direction: column-reverse;
            }

            .secondary-button,
            .primary-button {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <main class="page-shell">
        <section class="register-card" aria-label="Form registrasi agent Kresek.in">
            <a class="brand" href="{{ url('/') }}" aria-label="Kresek.in">
                <img src="{{ asset('images/kresekin-bag-mark.svg') }}" alt="" aria-hidden="true">
                <span>Kresek.<strong>in</strong> Agent</span>
            </a>

            <div class="hero-copy">
                <h1>Bantu UMKM Tumbuh &amp; Dapatkan Penghasilan</h1>
                <p>Lengkapi data agent dan dokumen identitas untuk mulai bergabung dengan jaringan Kresek.in.</p>
            </div>

            <div class="section-title">
                <h2>Informasi Akun</h2>
                <p>Isi data berikut untuk mulai menjadi Agent Kresek.</p>
            </div>

            @if (session('status'))
                <div class="status-message" role="status">{{ session('status') }}</div>
            @endif

            @if ($errors->any())
                <div class="error-summary" role="alert">Periksa kembali data yang belum sesuai.</div>
            @endif

            <form class="register-form" method="POST" action="{{ route('agent.register.store') }}" enctype="multipart/form-data" novalidate>
                @csrf

                <div class="form-grid">
                    <div class="form-row full">
                        <label for="name">Nama Lengkap <span class="required">*</span></label>
                        <div class="field">
                            <input id="name" name="name" type="text" value="{{ old('name') }}" placeholder="Nama sesuai identitas" autocomplete="name" required autofocus>
                        </div>
                        @error('name')
                            <div class="field-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-row full compact-field">
                        <label for="email">EMAIL <span class="required">*</span></label>
                        <div class="field">
                            <input id="email" name="email" type="email" value="{{ old('email') }}" placeholder="Masukkan email aktif" autocomplete="email" required>
                        </div>
                        @error('email')
                            <div class="field-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-row full compact-field">
                        <label for="phone">NO WHATSAPP <span class="required">*</span></label>
                        <div class="field">
                            <input id="phone" name="phone" type="tel" value="{{ old('phone') }}" placeholder="Contoh: 08123456789" autocomplete="tel" required>
                        </div>
                        @error('phone')
                            <div class="field-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-row full">
                        <span class="field-label">Dokumen Identitas <span class="required">*</span></span>
                        <label class="upload-field" for="identity_document">
                            <span>
                                <span class="upload-title">Klik atau unggah file KTP/SIM/Passport ke sini</span>
                                <span class="upload-help">Format JPG, PNG, atau PDF. Maksimal 5MB.</span>
                            </span>
                            <input id="identity_document" name="identity_document" type="file" accept=".jpg,.jpeg,.png,.pdf" required>
                        </label>
                        @error('identity_document')
                            <div class="field-error">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div>
                    <div class="checkbox-row">
                        <input id="consent" name="consent" type="checkbox" value="1" @checked(old('consent')) required>
                        <label for="consent">
                            Saya menyetujui pemrosesan data pribadi dan dokumen identitas untuk kebutuhan registrasi serta verifikasi Agent Kresek.in.
                        </label>
                    </div>
                    @error('consent')
                        <div class="field-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="actions">
                    <a class="secondary-button" href="{{ url('/') }}">Kembali</a>
                    <button class="primary-button" type="submit">Daftar Sekarang</button>
                </div>
            </form>

            <p class="copyright">&copy; {{ now()->year }} Kresek.in. All rights reserved.</p>
        </section>
    </main>
</body>
</html>
