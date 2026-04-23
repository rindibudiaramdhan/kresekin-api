@extends('agent.layout', ['title' => 'Agent Registration'])

@section('content')
<div class="card shadow-sm mx-auto mt-5" style="max-width: 520px;">
    <div class="card-body">
    <h1 class="h3 mb-2">Registrasi Agent</h1>
    <p class="text-secondary">Buat akun untuk role agent.</p>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('agent.register.store') }}" class="vstack gap-3">
        @csrf
        <div>
            <label for="name" class="form-label">Nama</label>
            <input class="form-control" id="name" name="name" type="text" value="{{ old('name') }}" required autofocus>
            @error('name')
                <div class="text-danger small">{{ $message }}</div>
            @enderror
        </div>
        <div>
            <label for="email" class="form-label">Email</label>
            <input class="form-control" id="email" name="email" type="email" value="{{ old('email') }}" required>
            @error('email')
                <div class="text-danger small">{{ $message }}</div>
            @enderror
        </div>
        <div>
            <label for="password" class="form-label">Password</label>
            <input class="form-control" id="password" name="password" type="password" required>
            @error('password')
                <div class="text-danger small">{{ $message }}</div>
            @enderror
        </div>
        <div>
            <label for="password_confirmation" class="form-label">Konfirmasi Password</label>
            <input class="form-control" id="password_confirmation" name="password_confirmation" type="password" required>
        </div>
        <button class="btn btn-success" type="submit">Daftar Agent</button>
    </form>
</div>
    </div>
@endsection
