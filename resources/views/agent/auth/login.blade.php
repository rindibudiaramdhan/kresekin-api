@extends('agent.layout', ['title' => 'Agent Login'])

@section('content')
<div class="card shadow-sm mx-auto mt-5" style="max-width: 480px;">
    <div class="card-body">
    <h1 class="h3 mb-2">Agent Login</h1>
    <p class="text-secondary">Masuk dengan akun agent untuk mengakses dashboard.</p>

    @if ($errors->any())
        <div class="alert alert-danger">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('agent.login.store') }}" class="vstack gap-3">
        @csrf
        <div>
            <label for="email" class="form-label">Email</label>
            <input class="form-control" id="email" name="email" type="email" value="{{ old('email') }}" required autofocus>
        </div>
        <div>
            <label for="password" class="form-label">Password</label>
            <input class="form-control" id="password" name="password" type="password" required>
        </div>
        <button class="btn btn-success" type="submit">Login</button>
    </form>

    <div class="mt-3 text-secondary">
        Belum punya akun?
        <a href="{{ route('agent.register') }}" class="fw-semibold">Daftar agent</a>
    </div>
    </div>
</div>
@endsection
