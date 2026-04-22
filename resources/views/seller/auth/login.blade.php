@extends('seller.layout', ['title' => 'Seller Login'])

@section('content')
<div class="card" style="max-width: 480px; margin: 64px auto;">
    <h1 style="margin-top:0;">Seller Login</h1>
    <p class="muted">Masuk dengan akun seller untuk mengelola produk.</p>

    @if ($errors->any())
        <div class="alert" style="background:#fef2f2;color:#991b1b;border-color:#fecaca;">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('seller.login.store') }}" class="grid" style="gap:14px;">
        @csrf
        <div>
            <label for="email">Email</label>
            <input class="input" id="email" name="email" type="email" value="{{ old('email') }}" required autofocus>
        </div>
        <div>
            <label for="password">Password</label>
            <input class="input" id="password" name="password" type="password" required>
        </div>
        <button class="btn" type="submit">Login</button>
    </form>
</div>
@endsection
