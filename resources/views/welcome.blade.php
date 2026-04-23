<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Laravel') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(180deg, #f8fafc 0%, #ecfeff 100%); }
    </style>
</head>
<body class="min-vh-100 d-flex align-items-center">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow-lg border-0">
                    <div class="card-body p-5">
                        <h1 class="display-5 fw-bold mb-3">Kresekin API</h1>
                        <p class="lead text-secondary mb-4">Panel login dan manajemen untuk seller serta agent tersedia di aplikasi ini.</p>
                        <div class="d-flex flex-wrap gap-2">
                            <a class="btn btn-success" href="{{ route('agent.login') }}">Agent Login</a>
                            <a class="btn btn-outline-success" href="{{ route('agent.register') }}">Agent Register</a>
                            <a class="btn btn-outline-secondary" href="{{ route('seller.login') }}">Seller Login</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
