@extends('agent.layout', ['title' => 'Agent Registration'])

@section('content')
<div class="card" style="max-width: 520px; margin: 64px auto;">
    <h1 style="margin-top:0;">Registrasi Agent</h1>
    <p class="muted">Buat akun untuk role agent.</p>

    @if (session('status'))
        <div class="alert">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert" style="background:#fef2f2;color:#991b1b;border-color:#fecaca;">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('agent.register.store') }}" class="grid" style="gap:14px;">
        @csrf
        <div>
            <label for="name">Nama</label>
            <input class="input" id="name" name="name" type="text" value="{{ old('name') }}" required autofocus>
            @error('name')
                <div class="error">{{ $message }}</div>
            @enderror
        </div>
        <div>
            <label for="email">Email</label>
            <input class="input" id="email" name="email" type="email" value="{{ old('email') }}" required>
            @error('email')
                <div class="error">{{ $message }}</div>
            @enderror
        </div>
        <div>
            <label for="password">Password</label>
            <input class="input" id="password" name="password" type="password" required>
            @error('password')
                <div class="error">{{ $message }}</div>
            @enderror
        </div>
        <div>
            <label for="password_confirmation">Konfirmasi Password</label>
            <input class="input" id="password_confirmation" name="password_confirmation" type="password" required>
        </div>
        <button class="btn" type="submit">Daftar Agent</button>
    </form>
</div>
@endsection
