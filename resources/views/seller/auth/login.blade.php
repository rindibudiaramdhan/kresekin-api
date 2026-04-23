@extends('seller.layout', ['title' => 'Seller Login'])

@section('content')
<div class="card shadow-sm mx-auto mt-5" style="max-width: 480px;">
    <div class="card-body">
    <h1 class="h3 mb-2">Seller Login</h1>
    <p class="text-secondary">Masuk dengan akun seller untuk mengelola produk.</p>

    @if ($errors->any())
        <div class="alert alert-danger">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('seller.login.store') }}" class="vstack gap-3">
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
    </div>
</div>
@endsection
