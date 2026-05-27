@extends('agent.layout', ['title' => 'Daftar Agent'])

@push('styles')
<style>
    body {
        background: #fff;
        color: #1c1f26;
        font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    }

    .panel-shell {
        max-width: none;
        padding: 0 !important;
    }

    .agent-register-page {
        min-height: 100vh;
        min-height: 100dvh;
        padding: 104px 24px 34px;
        background: #fff;
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    .agent-register-header {
        flex: 0 0 auto;
        text-align: center;
        margin-bottom: 66px;
    }

    .agent-register-brand {
        margin: 0;
        color: #10bdc8;
        font-size: 38px;
        line-height: 1.05;
        font-weight: 850;
        letter-spacing: 0;
    }

    .agent-register-brand span {
        color: #ffbd19;
    }

    .agent-register-brand small {
        color: #10bdc8;
        font-size: 1em;
        font-weight: 850;
    }

    .agent-register-tagline {
        margin: 14px 0 0;
        color: #435371;
        font-size: 22px;
        font-weight: 500;
        letter-spacing: 0;
    }

    .agent-register-card {
        width: min(860px, 100%);
        max-height: 100%;
        display: flex;
        flex-direction: column;
        padding: 50px 34px 32px;
        border: 1px solid #c5cad8;
        border-radius: 16px;
        background: #ffffff;
        box-shadow: none;
    }

    .agent-card-title {
        margin: 0 0 10px;
        color: #1b1f26;
        font-size: 29px;
        line-height: 1.12;
        font-weight: 820;
    }

    .agent-card-subtitle {
        margin: 0;
        color: #566985;
        font-size: 19px;
        line-height: 1.3;
        font-weight: 430;
    }

    .agent-register-divider {
        margin: 24px 0 34px;
        border: 0;
        border-top: 1px solid #dde0e6;
        opacity: 1;
    }

    .agent-register-form {
        flex: 1 1 auto;
        min-height: 0;
        display: flex;
        flex-direction: column;
    }

    .agent-register-fields {
        display: grid;
        gap: 20px;
    }

    .agent-register-field label {
        display: block;
        margin-bottom: 9px;
        color: #484b58;
        font-size: 16px;
        line-height: 1.1;
        font-weight: 820;
        letter-spacing: 1.2px;
        text-transform: uppercase;
    }

    .required-mark {
        color: #ff2d2d;
    }

    .agent-register-input {
        width: 100%;
        height: 68px;
        border: 1.5px solid #bfc5d5;
        border-radius: 10px;
        padding: 0 24px;
        color: #1f2530;
        background: #ffffff;
        font-size: 22px;
        line-height: 1.1;
        outline: none;
        transition: border-color .18s ease, box-shadow .18s ease;
    }

    .agent-register-input::placeholder {
        color: #aeb4bf;
        opacity: 1;
    }

    .agent-register-input:focus {
        border-color: #13bdc8;
        box-shadow: 0 0 0 4px rgba(19, 189, 200, .12);
    }

    .agent-register-error {
        margin-top: 4px;
        color: #e12d39;
        font-size: 13px;
        line-height: 1.2;
    }

    .agent-upload-field {
        position: relative;
    }

    .agent-upload-zone {
        min-height: 140px;
        margin-bottom: 0;
        border: 2px dashed #b7b7b7;
        border-radius: 10px;
        background: #f7f8fb;
        color: #464c59;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 8px;
        text-align: center;
        text-transform: none;
        letter-spacing: 0;
        cursor: pointer;
        transition: border-color .18s ease, background-color .18s ease;
    }

    .agent-upload-zone:hover,
    .agent-upload-zone:focus-within {
        border-color: #13bdc8;
        background: #f2fbfc;
    }

    .agent-upload-zone input {
        position: absolute;
        inline-size: 1px;
        block-size: 1px;
        opacity: 0;
        pointer-events: none;
    }

    .agent-upload-icon {
        width: 60px;
        height: 46px;
        color: #50627e;
    }

    .agent-upload-text {
        font-size: 19px;
        line-height: 1.25;
        font-weight: 450;
    }

    .agent-upload-note {
        color: #111111;
        font-size: 17px;
        line-height: 1.25;
        font-weight: 800;
    }

    .agent-consent {
        min-height: 122px;
        margin-top: 34px;
        padding: 22px 18px;
        display: grid;
        grid-template-columns: auto 1fr;
        align-items: start;
        gap: 22px;
        border: 1.5px solid #b8b8b8;
        border-radius: 12px;
        background: #f8f8f8;
        color: #555555;
        font-size: 16px;
        line-height: 1.42;
        font-weight: 760;
    }

    .agent-consent input {
        width: 36px;
        height: 36px;
        accent-color: #12bdc6;
        flex: 0 0 auto;
        margin-top: 4px;
    }

    .agent-register-actions {
        margin-top: 96px;
        padding-top: 34px;
        border-top: 1px solid #dde0e6;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 20px;
    }

    .agent-back-link {
        color: #a7b0c2;
        text-decoration: none;
        font-size: 22px;
        line-height: 1;
        font-weight: 520;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .agent-back-link:hover {
        color: #7e8aa0;
    }

    .agent-submit {
        width: 300px;
        height: 68px;
        border: 0;
        border-radius: 10px;
        background: #14bec8;
        color: #ffffff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 14px;
        font-size: 22px;
        font-weight: 520;
        white-space: nowrap;
    }

    .agent-submit:hover {
        background: #0eacb6;
    }

    .agent-register-footer {
        flex: 0 0 auto;
        margin: 46px 0 0;
        color: #293959;
        text-align: center;
        font-size: 17px;
        line-height: 1.2;
        font-weight: 800;
        letter-spacing: 1px;
    }

    @media (max-height: 900px) and (min-width: 901px) {
        .agent-register-page {
            padding-top: 28px;
            padding-bottom: 20px;
        }

        .agent-register-header {
            margin-bottom: 26px;
        }

        .agent-register-card {
            padding-top: 34px;
            padding-bottom: 28px;
        }

        .agent-register-divider {
            margin-bottom: 24px;
        }

        .agent-register-fields {
            gap: 14px;
        }

        .agent-register-input {
            height: 58px;
        }

        .agent-upload-zone {
            min-height: 118px;
        }

        .agent-consent {
            min-height: 96px;
            margin-top: 22px;
            padding-top: 16px;
            padding-bottom: 16px;
        }

        .agent-register-actions {
            margin-top: 44px;
            padding-top: 24px;
        }

        .agent-register-footer {
            margin-top: 24px;
        }
    }

    @media (max-width: 900px) {
        .agent-register-page {
            height: auto;
            min-height: 100dvh;
            overflow: auto;
            padding: 28px 18px 22px;
        }

        .agent-register-header {
            margin-bottom: 32px;
        }

        .agent-register-brand {
            font-size: 32px;
        }

        .agent-register-tagline {
            margin-top: 10px;
            font-size: 18px;
        }

        .agent-register-card {
            padding: 30px 20px 24px;
        }

        .agent-register-divider {
            margin: 18px 0 24px;
        }

        .agent-register-fields {
            gap: 16px;
        }

        .agent-register-input {
            height: 56px;
            padding: 0 18px;
            font-size: 18px;
        }

        .agent-upload-zone {
            min-height: 126px;
            padding: 18px;
        }

        .agent-upload-text {
            font-size: 16px;
        }

        .agent-upload-note {
            font-size: 15px;
        }

        .agent-consent {
            min-height: 0;
            margin-top: 22px;
            padding: 18px 16px;
            gap: 14px;
            font-size: 14px;
        }

        .agent-consent input {
            width: 30px;
            height: 30px;
        }

        .agent-register-actions {
            margin-top: 42px;
            padding-top: 24px;
            flex-direction: column-reverse;
            align-items: stretch;
        }

        .agent-submit {
            width: 100%;
        }

        .agent-back-link {
            justify-content: center;
        }

        .agent-register-footer {
            margin-top: 26px;
            font-size: 14px;
        }
    }
</style>
@endpush

@section('content')
<main class="agent-register-page">
    <header class="agent-register-header">
        <h1 class="agent-register-brand">Kresek<span>.</span><span>in</span> <small>Agent</small></h1>
        <p class="agent-register-tagline">Bantu UMKM Tumbuh &amp; Dapatkan Penghasilan</p>
    </header>

    <section class="agent-register-card" aria-label="Form registrasi agent Kresek.in">
        <h2 class="agent-card-title">Informasi Akun</h2>
        <p class="agent-card-subtitle">Isi data berikut untuk mulai menjadi Agent Kresek</p>
        <hr class="agent-register-divider">

        @if (session('status'))
            <div class="alert alert-success py-2 mb-3">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="visually-hidden" role="alert">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('agent.register.store') }}" class="agent-register-form" enctype="multipart/form-data" data-agent-register-form>
            @csrf
            <div class="agent-register-fields">
                <div class="agent-register-field">
                    <label for="name">Nama Lengkap <span class="required-mark">*</span></label>
                    <input class="agent-register-input" id="name" name="name" type="text" value="{{ old('name') }}" placeholder="Masukkan nama lengkap" required autofocus>
                    @error('name')
                        <div class="agent-register-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="agent-register-field">
                    <label for="email">Email <span class="required-mark">*</span></label>
                    <input class="agent-register-input" id="email" name="email" type="email" value="{{ old('email') }}" placeholder="Masukkan email aktif" required>
                    @error('email')
                        <div class="agent-register-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="agent-register-field">
                    <label for="phone">No Whatsapp <span class="required-mark">*</span></label>
                    <input class="agent-register-input" id="phone" name="phone" type="tel" value="{{ old('phone') }}" placeholder="Contoh: 08123456789" required>
                    @error('phone')
                        <div class="agent-register-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="agent-register-field">
                    <label for="password">Password <span class="required-mark">*</span></label>
                    <input class="agent-register-input" id="password" name="password" type="password" placeholder="Masukkan password" required>
                    <input id="password_confirmation" name="password_confirmation" type="hidden">
                    @error('password')
                        <div class="agent-register-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="agent-register-field agent-upload-field">
                    <label for="identity_document">Dokumen Identitas <span class="required-mark">*</span></label>
                    <label class="agent-upload-zone" for="identity_document">
                        <svg class="agent-upload-icon" viewBox="0 0 64 48" fill="none" aria-hidden="true">
                            <path d="M41.7 38H49C55.1 38 60 33.2 60 27.3C60 21.4 55.1 16.6 49 16.6C47.8 16.6 46.6 16.8 45.5 17.2C42.9 9.5 35.6 4 27 4C16.2 4 7.5 12.5 7.5 23C7.5 23.8 7.6 24.7 7.7 25.5C3.2 26.8 0 30.8 0 35.6C0 41.3 4.7 46 10.5 46H23.2" stroke="currentColor" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M32 40V18M32 18L22.5 27.5M32 18L41.5 27.5" stroke="currentColor" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <span class="agent-upload-text">Klik atau unggah file KTP/SIM/Passport ke sini</span>
                        <span class="agent-upload-note">Format: JPG, PNG, PDF up to 5MB</span>
                        <input id="identity_document" name="identity_document" type="file" accept=".jpg,.jpeg,.png,.pdf" required>
                    </label>
                    @error('identity_document')
                        <div class="agent-register-error">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <label class="agent-consent" for="terms">
                <input id="terms" name="terms" type="checkbox" value="1" required @checked(old('terms'))>
                <span>Saya setuju data saya diproses untuk personalisasi, analitik demi peningkatan layanan dan komunikasi sesuai Kebijakan &amp; Ketentuan Layanan yang berlaku</span>
            </label>
            @error('terms')
                <div class="agent-register-error">{{ $message }}</div>
            @enderror

            <div class="agent-register-actions">
                <a class="agent-back-link" href="{{ route('agent.login') }}">
                    <span aria-hidden="true">←</span>
                    <span>Kembali</span>
                </a>
                <button class="agent-submit" type="submit">
                    <span>Daftar Sekarang</span>
                    <span aria-hidden="true">→</span>
                </button>
            </div>
        </form>
    </section>

    <p class="agent-register-footer">© 2026 Kresek.in. All Rights Reserved.</p>
</main>

<script>
    const registerForm = document.querySelector('[data-agent-register-form]');
    const password = document.querySelector('#password');
    const passwordConfirmation = document.querySelector('#password_confirmation');

    function syncPasswordConfirmation() {
        if (password && passwordConfirmation) {
            passwordConfirmation.value = password.value;
        }
    }

    password?.addEventListener('input', syncPasswordConfirmation);
    registerForm?.addEventListener('submit', syncPasswordConfirmation);
</script>
@endsection
