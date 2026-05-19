@extends('agent.layout', ['title' => 'Agent Login'])

@push('styles')
<style>
    body {
        background: #ffffff;
        color: #141821;
        font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    }

    .panel-shell {
        max-width: none;
        padding: 0 !important;
    }

    .agent-login-page {
        min-height: 100vh;
        display: grid;
        place-items: center;
        padding: 56px;
        background: #ffffff;
    }

    .agent-login-shell {
        width: min(1760px, 100%);
        min-height: 1170px;
        display: grid;
        grid-template-columns: 1fr 1fr;
        overflow: hidden;
        border-radius: 16px;
        background: #ffffff;
        box-shadow: 0 18px 45px rgba(15, 23, 42, .08);
    }

    .agent-login-form-panel {
        display: flex;
        flex-direction: column;
        justify-content: center;
        padding: 88px 58px 44px;
    }

    .agent-login-inner {
        width: min(100%, 760px);
        margin: 0 auto;
    }

    .brand-mark {
        display: inline-flex;
        align-items: center;
        gap: 18px;
        margin-bottom: 52px;
    }

    .brand-bag {
        width: 54px;
        height: 54px;
        display: grid;
        place-items: center;
        border-radius: 13px 13px 17px 17px;
        background: linear-gradient(180deg, #18c8ce 0%, #0bb5bf 100%);
        color: #ffffff;
        position: relative;
    }

    .brand-bag::before {
        content: "";
        width: 24px;
        height: 17px;
        position: absolute;
        top: 10px;
        border: 4px solid #ffffff;
        border-bottom: 0;
        border-radius: 14px 14px 0 0;
    }

    .brand-bag::after {
        content: "";
        width: 20px;
        height: 12px;
        position: absolute;
        bottom: 15px;
        border-bottom: 4px solid #b7df25;
        border-radius: 0 0 18px 18px;
    }

    .brand-text {
        font-size: 46px;
        line-height: 1;
        font-weight: 800;
        letter-spacing: 0;
        color: #12bdc6;
    }

    .brand-text span {
        color: #ffb000;
    }

    .agent-login-title {
        margin: 0 0 16px;
        font-size: 54px;
        line-height: 1.08;
        font-weight: 800;
        letter-spacing: 0;
    }

    .agent-login-subtitle {
        margin: 0 0 58px;
        color: #525866;
        font-size: 28px;
        line-height: 1.4;
        font-weight: 400;
    }

    .agent-auth-form {
        display: grid;
        gap: 26px;
    }

    .agent-field label {
        display: block;
        margin-bottom: 10px;
        color: #4b4e5d;
        font-size: 22px;
        font-weight: 700;
    }

    .agent-input-wrap {
        min-height: 84px;
        display: flex;
        align-items: center;
        gap: 22px;
        padding: 0 26px;
        border: 2px solid #e1e5ed;
        border-radius: 14px;
        background: #f9fafc;
        color: #747987;
        transition: border-color .18s ease, background .18s ease, box-shadow .18s ease;
    }

    .agent-field.has-error .agent-input-wrap {
        border-color: #ff3232;
        background: #fbfcff;
    }

    .agent-input-wrap:focus-within {
        border-color: #10bdc8;
        background: #ffffff;
        box-shadow: 0 0 0 4px rgba(16, 189, 200, .12);
    }

    .agent-input-icon,
    .password-toggle {
        width: 34px;
        height: 34px;
        flex: 0 0 34px;
        color: #7b8190;
    }

    .agent-input {
        width: 100%;
        min-width: 0;
        border: 0;
        outline: 0;
        background: transparent;
        color: #232734;
        font-size: 25px;
        line-height: 1.2;
    }

    .agent-input::placeholder {
        color: #828795;
        opacity: 1;
    }

    .password-toggle {
        border: 0;
        background: transparent;
        padding: 0;
        display: grid;
        place-items: center;
    }

    .agent-field-meta {
        min-height: 26px;
        display: flex;
        justify-content: flex-end;
        align-items: center;
        gap: 20px;
        margin-top: 8px;
        font-size: 21px;
        line-height: 1.2;
    }

    .agent-error {
        color: #ff3232;
        letter-spacing: 1.2px;
    }

    .agent-link {
        color: #10bdc8;
        text-decoration: none;
        font-weight: 700;
    }

    .agent-link:hover {
        color: #079aa6;
    }

    .agent-submit {
        width: 100%;
        height: 84px;
        margin-top: 12px;
        border: 0;
        border-radius: 12px;
        background: #14bec8;
        color: #ffffff;
        font-size: 25px;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 20px;
    }

    .agent-submit:hover {
        background: #0eadb7;
        color: #ffffff;
    }

    .agent-register-copy {
        margin-top: 78px;
        color: #555b67;
        text-align: center;
        font-size: 25px;
    }

    .agent-login-footer {
        margin-top: 168px;
        text-align: center;
        color: #bcc0ca;
        font-size: 23px;
        font-weight: 700;
        letter-spacing: 1.5px;
    }

    .agent-visual-panel {
        position: relative;
        overflow: hidden;
        min-height: 1170px;
        border-radius: 0 16px 16px 0;
        color: #ffffff;
        background:
            linear-gradient(180deg, rgba(235, 245, 255, .84) 0%, rgba(190, 220, 250, .58) 43%, rgba(0, 115, 171, .96) 100%),
            radial-gradient(circle at 66% 38%, rgba(255, 255, 255, .95) 0 9%, transparent 24%),
            linear-gradient(132deg, #ffffff 0 16%, #d7e8fc 16% 47%, #b3dcf4 47% 100%);
    }

    .agent-visual-panel::before {
        content: "";
        position: absolute;
        inset: 0;
        background:
            linear-gradient(103deg, rgba(255, 255, 255, .98) 0 14%, transparent 14.2%),
            linear-gradient(90deg, rgba(255, 255, 255, .58), transparent 34%),
            radial-gradient(circle at 82% 11%, rgba(255, 255, 255, .85), transparent 10%),
            repeating-linear-gradient(90deg, transparent 0 118px, rgba(7, 56, 98, .06) 119px 120px);
        pointer-events: none;
    }

    .agent-visual-content {
        position: relative;
        z-index: 2;
        height: 100%;
        padding: 40px 36px 34px;
        display: flex;
        flex-direction: column;
    }

    .visual-brand {
        display: inline-flex;
        align-items: center;
        gap: 12px;
        color: #0b9fb4;
        font-size: 40px;
        font-weight: 800;
        line-height: 1;
    }

    .visual-brand .brand-bag {
        width: 48px;
        height: 48px;
        border-radius: 12px 12px 15px 15px;
    }

    .visual-hero-copy {
        margin-top: 58px;
        max-width: 470px;
        color: #082343;
    }

    .visual-hero-copy h2 {
        margin: 0;
        font-size: 55px;
        line-height: 1.08;
        font-weight: 850;
        letter-spacing: 0;
    }

    .visual-hero-copy h2 span,
    .visual-hero-copy p strong {
        color: #0ca6c0;
    }

    .visual-hero-copy p {
        width: 280px;
        margin: 34px 0 0;
        font-size: 19px;
        line-height: 1.35;
        font-weight: 700;
    }

    .scribble {
        position: absolute;
        top: 122px;
        left: 350px;
        color: #079fc1;
        font-size: 22px;
        line-height: 1.45;
        font-weight: 800;
        transform: rotate(-7deg);
    }

    .commission-card {
        position: absolute;
        top: 92px;
        right: 28px;
        width: 314px;
        min-height: 132px;
        padding: 22px 24px;
        border-radius: 18px;
        background: rgba(245, 250, 255, .88);
        box-shadow: 0 18px 32px rgba(11, 45, 91, .18);
        color: #14375d;
    }

    .commission-card b {
        display: block;
        margin-bottom: 8px;
        font-size: 17px;
    }

    .commission-card strong {
        display: block;
        color: #0b98bd;
        font-size: 30px;
        line-height: 1.05;
    }

    .commission-card small {
        color: #6c7888;
        font-size: 15px;
    }

    .wallet-dot {
        position: absolute;
        top: 26px;
        right: 24px;
        width: 72px;
        height: 72px;
        display: grid;
        place-items: center;
        border-radius: 50%;
        background: linear-gradient(160deg, #10c3b9, #027d78);
        color: #ffffff;
    }

    .coin {
        position: absolute;
        top: 235px;
        right: 58px;
        width: 78px;
        height: 78px;
        display: grid;
        place-items: center;
        border-radius: 50%;
        background: radial-gradient(circle at 30% 25%, #ffe79a, #efa727 56%, #bd7818);
        box-shadow: 0 12px 22px rgba(129, 80, 9, .22);
        color: #ffffff;
        font-size: 34px;
        font-weight: 850;
        transform: rotate(18deg);
    }

    .seller-scene {
        position: absolute;
        right: 86px;
        bottom: 386px;
        width: 430px;
        height: 410px;
    }

    .person {
        position: absolute;
        bottom: 0;
        width: 172px;
        height: 318px;
        border-radius: 82px 82px 26px 26px;
        background: linear-gradient(180deg, #c4dcf2 0 21%, #14a5bc 21% 100%);
        box-shadow: 0 20px 35px rgba(5, 55, 92, .2);
    }

    .person::before {
        content: "";
        position: absolute;
        top: -54px;
        left: 42px;
        width: 88px;
        height: 88px;
        border-radius: 50%;
        background: #f3c2a5;
        box-shadow: inset -10px 0 0 rgba(172, 105, 85, .15);
    }

    .person::after {
        content: "";
        position: absolute;
        top: -68px;
        left: 22px;
        width: 122px;
        height: 106px;
        border-radius: 64px 64px 40px 34px;
        background: #cbb9b1;
        opacity: .96;
    }

    .person-one {
        left: 0;
        background: linear-gradient(180deg, #d9d3d8 0 18%, #11879a 18% 100%);
    }

    .person-two {
        right: 0;
        background: linear-gradient(180deg, #cae6ff 0 19%, #78b4eb 19% 100%);
    }

    .person-two::after {
        background: #172338;
        left: 30px;
        height: 116px;
    }

    .tote-bag {
        position: absolute;
        left: 104px;
        bottom: 42px;
        width: 122px;
        height: 164px;
        border-radius: 8px 8px 18px 18px;
        background: rgba(4, 192, 213, .78);
        box-shadow: inset 0 0 0 2px rgba(255, 255, 255, .24);
    }

    .phone-shape {
        position: absolute;
        right: 128px;
        bottom: 72px;
        width: 54px;
        height: 96px;
        border-radius: 12px;
        background: #132039;
        transform: rotate(-10deg);
    }

    .bottom-program-card {
        position: relative;
        z-index: 3;
        margin-top: auto;
        min-height: 485px;
        padding: 46px 48px;
        overflow: hidden;
        border-radius: 0 0 16px 16px;
        background:
            linear-gradient(180deg, rgba(3, 56, 105, .74), rgba(0, 95, 157, .98)),
            radial-gradient(circle at 50% 25%, rgba(255, 255, 255, .35), transparent 30%);
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, .26);
    }

    .bottom-program-card::before,
    .bottom-program-card::after {
        content: "";
        position: absolute;
        left: 34px;
        right: 34px;
        height: 108px;
        border-radius: 18px;
        background: rgba(245, 250, 255, .72);
        filter: blur(5px);
    }

    .bottom-program-card::before {
        top: 166px;
    }

    .bottom-program-card::after {
        bottom: 34px;
    }

    .bottom-program-card > * {
        position: relative;
        z-index: 2;
    }

    .program-kicker {
        margin-bottom: 28px;
        color: rgba(255, 255, 255, .8);
        font-size: 30px;
        font-weight: 800;
        letter-spacing: 2px;
    }

    .program-title {
        max-width: 720px;
        margin: 0;
        color: #ffffff;
        font-size: 36px;
        line-height: 1.25;
        font-weight: 800;
    }

    .visual-register-button {
        position: absolute;
        z-index: 4;
        left: 50%;
        bottom: 98px;
        transform: translateX(-50%);
        min-width: 310px;
        height: 96px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 22px;
        background: #14bec8;
        color: #ffffff;
        text-decoration: none;
        font-size: 30px;
        font-weight: 800;
        box-shadow: 0 20px 30px rgba(0, 135, 149, .28);
    }

    @media (max-width: 1400px) {
        .agent-login-page {
            padding: 24px;
        }

        .agent-login-shell {
            min-height: 900px;
        }

        .agent-login-form-panel,
        .agent-visual-panel {
            min-height: 900px;
        }

        .agent-login-title {
            font-size: 42px;
        }

        .agent-login-subtitle {
            font-size: 22px;
        }

        .brand-text {
            font-size: 36px;
        }

        .agent-input {
            font-size: 21px;
        }
    }

    @media (max-width: 992px) {
        .agent-login-page {
            display: block;
            min-height: auto;
            padding: 18px;
        }

        .agent-login-shell {
            display: block;
            min-height: 0;
        }

        .agent-login-form-panel {
            min-height: auto;
            padding: 42px 22px 30px;
        }

        .agent-login-inner {
            width: 100%;
        }

        .agent-visual-panel {
            min-height: 720px;
            border-radius: 0 0 16px 16px;
        }

        .agent-login-footer {
            margin-top: 56px;
            font-size: 16px;
        }

        .agent-register-copy {
            margin-top: 42px;
            font-size: 18px;
        }
    }

    @media (max-width: 640px) {
        .agent-login-page {
            padding: 0;
        }

        .agent-login-shell {
            border-radius: 0;
            box-shadow: none;
        }

        .brand-mark {
            margin-bottom: 34px;
        }

        .brand-text {
            font-size: 32px;
        }

        .agent-login-title {
            font-size: 36px;
        }

        .agent-login-subtitle {
            margin-bottom: 36px;
            font-size: 18px;
        }

        .agent-input-wrap {
            min-height: 68px;
            padding: 0 18px;
        }

        .agent-input {
            font-size: 18px;
        }

        .agent-field label,
        .agent-field-meta {
            font-size: 16px;
        }

        .agent-submit {
            height: 68px;
            font-size: 20px;
        }

        .agent-visual-panel {
            min-height: 620px;
        }

        .visual-hero-copy h2 {
            font-size: 40px;
        }

        .commission-card,
        .scribble,
        .seller-scene,
        .coin {
            transform: scale(.78);
            transform-origin: top right;
        }

        .commission-card {
            right: 14px;
        }

        .seller-scene {
            right: 20px;
            bottom: 280px;
        }

        .bottom-program-card {
            min-height: 330px;
            padding: 32px 24px;
        }

        .program-kicker {
            font-size: 18px;
        }

        .program-title {
            font-size: 24px;
        }

        .visual-register-button {
            min-width: 230px;
            height: 70px;
            bottom: 70px;
            font-size: 22px;
        }
    }
</style>
@endpush

@section('content')
<main class="agent-login-page">
    <section class="agent-login-shell" aria-label="Login agent Kresek.in">
        <div class="agent-login-form-panel">
            <div class="agent-login-inner">
                <a class="brand-mark text-decoration-none" href="{{ route('agent.login') }}" aria-label="Kresek.in">
                    <span class="brand-bag" aria-hidden="true"></span>
                    <span class="brand-text">kresek.<span>in</span></span>
                </a>

                <h1 class="agent-login-title">Selamat Datang!</h1>
                <p class="agent-login-subtitle">Masuk untuk mulai monitoring UMKM</p>

                @if ($errors->any())
                    <div class="visually-hidden" role="alert">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('agent.login.store') }}" class="agent-auth-form">
                    @csrf
                    <div class="agent-field @error('email') has-error @enderror">
                        <label for="email">Email</label>
                        <div class="agent-input-wrap">
                            <svg class="agent-input-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M4 6h16v12H4V6Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                                <path d="m4 7 8 6 8-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <input class="agent-input" id="email" name="email" type="email" value="{{ old('email') }}" placeholder="admin@kresek.in" required autofocus>
                        </div>
                        <div class="agent-field-meta">
                            @error('email')
                                <span class="agent-error">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="agent-field @error('password') has-error @enderror">
                        <div class="d-flex justify-content-between align-items-center gap-3">
                            <label for="password">Password</label>
                            <a class="agent-link mb-2" href="#">Lupa password?</a>
                        </div>
                        <div class="agent-input-wrap">
                            <svg class="agent-input-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M7 10V8a5 5 0 0 1 10 0v2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                <path d="M6 10h12v10H6V10Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                                <path d="M12 14v2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                            <input class="agent-input" id="password" name="password" type="password" placeholder="••••••••" required>
                            <button class="password-toggle" type="button" aria-label="Tampilkan password" data-password-toggle>
                                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                                    <path d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" stroke="currentColor" stroke-width="2"/>
                                </svg>
                            </button>
                        </div>
                        <div class="agent-field-meta">
                            @error('password')
                                <span class="agent-error">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <button class="agent-submit" type="submit">
                        <span>Masuk</span>
                        <svg width="36" height="36" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M5 12h14" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/>
                            <path d="m13 6 6 6-6 6" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                </form>

                <p class="agent-register-copy">
                    Belum punya akun agent?
                    <a class="agent-link" href="{{ route('agent.register') }}">Daftar Sekarang</a>
                </p>

                <p class="agent-login-footer">© 2026 Kresek.in. All rights reserved.</p>
            </div>
        </div>

        <aside class="agent-visual-panel" aria-label="Kresek agent program">
            <div class="agent-visual-content">
                <div class="visual-brand">
                    <span class="brand-bag" aria-hidden="true"></span>
                    <span>kresek.in</span>
                </div>

                <div class="visual-hero-copy">
                    <h2>Agen Seller UMKM <span>Kresek.in</span></h2>
                    <p>Bersama kita, dukung UMKM Indonesia <strong>tumbuh lebih besar</strong> dan sejahtera.</p>
                </div>

                <div class="scribble">Merangkul UMKM<br>dengan Senyuman ◡</div>

                <div class="commission-card">
                    <b>Total Komisi</b>
                    <strong>Rp 24.680.000</strong>
                    <small>▲ 28.5% dari bulan lalu</small>
                    <span class="wallet-dot" aria-hidden="true">
                        <svg width="42" height="42" viewBox="0 0 24 24" fill="none">
                            <path d="M4 7h15a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H4V7Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                            <path d="M4 7 16 4v3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M16 13h5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </span>
                </div>

                <div class="coin" aria-hidden="true">Rp</div>

                <div class="seller-scene" aria-hidden="true">
                    <div class="person person-one"></div>
                    <div class="person person-two"></div>
                    <div class="tote-bag"></div>
                    <div class="phone-shape"></div>
                </div>

                <div class="bottom-program-card">
                    <div class="program-kicker">KRESEK AGENT PROGRAM</div>
                    <p class="program-title">Bantu UMKM berkembang dan dapatkan komisi dari setiap transaksi seller binaan anda</p>
                    <a class="visual-register-button" href="{{ route('agent.register') }}">Daftar Sekarang</a>
                </div>
            </div>
        </aside>
    </section>
</main>

<script>
    document.querySelector('[data-password-toggle]')?.addEventListener('click', function () {
        const input = document.querySelector('#password');
        if (!input) return;

        input.type = input.type === 'password' ? 'text' : 'password';
        this.setAttribute('aria-label', input.type === 'password' ? 'Tampilkan password' : 'Sembunyikan password');
    });
</script>
@endsection
