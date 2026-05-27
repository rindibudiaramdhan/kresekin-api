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
        height: 100vh;
        height: 100dvh;
        padding: 6px 34px 10px;
        overflow: hidden;
        background: #fff;
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    .agent-register-header {
        flex: 0 0 auto;
        text-align: center;
        margin-bottom: 10px;
    }

    .agent-register-brand {
        margin: 0;
        color: #10bdc8;
        font-size: clamp(32px, 3.7vw, 48px);
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
        margin: 6px 0 0;
        color: #435371;
        font-size: clamp(17px, 1.85vw, 24px);
        font-weight: 750;
        letter-spacing: .5px;
    }

    .agent-register-card {
        width: min(1120px, 100%);
        flex: 1 1 auto;
        min-height: 0;
        max-height: 100%;
        display: flex;
        flex-direction: column;
        padding: clamp(16px, 2.2vh, 26px) 46px clamp(14px, 2vh, 24px);
        border: 1px solid #c5cad8;
        border-radius: 18px;
        background: #ffffff;
        box-shadow: 0 18px 30px rgba(60, 87, 190, .24), 0 0 0 1px rgba(255, 255, 255, .72) inset;
    }

    .agent-card-title {
        margin: 0 0 8px;
        color: #1b1f26;
        font-size: clamp(24px, 3vh, 32px);
        line-height: 1.12;
        font-weight: 820;
    }

    .agent-card-subtitle {
        margin: 0;
        color: #566985;
        font-size: clamp(16px, 2.1vh, 22px);
        line-height: 1.3;
        font-weight: 430;
    }

    .agent-register-divider {
        margin: clamp(10px, 1.6vh, 16px) 0 clamp(8px, 1.4vh, 14px);
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
        gap: clamp(7px, 1vh, 11px);
    }

    .agent-register-field label {
        display: block;
        margin-bottom: 5px;
        color: #484b58;
        font-size: clamp(13px, 1.55vh, 16px);
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
        height: clamp(42px, 5.8vh, 52px);
        border: 1.5px solid #bfc5d5;
        border-radius: 13px;
        padding: 0 24px;
        color: #1f2530;
        background: #ffffff;
        font-size: clamp(18px, 2.25vh, 22px);
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

    .agent-consent {
        min-height: clamp(66px, 9.8vh, 88px);
        margin-top: clamp(9px, 1.4vh, 14px);
        padding: 12px 24px;
        display: grid;
        grid-template-columns: auto 1fr;
        align-items: center;
        gap: 24px;
        border: 1.5px solid #b8b8b8;
        border-radius: 16px;
        background: #f8f8f8;
        color: #555555;
        font-size: clamp(14px, 1.8vh, 18px);
        line-height: 1.42;
        font-weight: 760;
    }

    .agent-consent input {
        width: clamp(30px, 4vh, 38px);
        height: clamp(30px, 4vh, 38px);
        accent-color: #12bdc6;
        flex: 0 0 auto;
    }

    .agent-register-actions {
        margin-top: auto;
        padding-top: clamp(10px, 2vh, 18px);
        border-top: 1px solid #dde0e6;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 20px;
    }

    .agent-back-link {
        color: #a7b0c2;
        text-decoration: none;
        font-size: clamp(18px, 2.25vh, 22px);
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
        width: min(390px, 45%);
        height: clamp(48px, 6.2vh, 58px);
        border: 0;
        border-radius: 13px;
        background: #14bec8;
        color: #ffffff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 14px;
        font-size: clamp(20px, 2.3vh, 24px);
        font-weight: 520;
        white-space: nowrap;
    }

    .agent-submit:hover {
        background: #0eacb6;
    }

    .agent-register-footer {
        flex: 0 0 auto;
        margin: 8px 0 0;
        color: #293959;
        text-align: center;
        font-size: clamp(13px, 1.65vh, 17px);
        line-height: 1.2;
        font-weight: 800;
        letter-spacing: 1px;
    }

    @media (max-height: 760px) and (min-width: 901px) {
        .agent-register-page {
            padding-top: 6px;
            padding-bottom: 8px;
        }

        .agent-register-header {
            margin-bottom: 10px;
        }

        .agent-register-card {
            padding-left: 36px;
            padding-right: 36px;
        }

        .agent-register-brand {
            font-size: 38px;
        }

        .agent-register-tagline {
            margin-top: 6px;
            font-size: 20px;
        }

        .agent-consent {
            padding-top: 12px;
            padding-bottom: 12px;
        }
    }

    @media (max-width: 900px) {
        .agent-register-page {
            height: auto;
            min-height: 100dvh;
            overflow: auto;
            padding: 18px;
        }

        .agent-register-card {
            padding: 28px 20px 24px;
        }

        .agent-register-actions {
            flex-direction: column-reverse;
            align-items: stretch;
        }

        .agent-submit {
            width: 100%;
        }

        .agent-back-link {
            justify-content: center;
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

        <form method="POST" action="{{ route('agent.register.store') }}" class="agent-register-form" data-agent-register-form>
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
