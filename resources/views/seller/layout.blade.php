<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Seller Panel' }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(180deg, #f8fafc 0%, #eef2ff 100%); }
        .panel-shell { max-width: 1200px; }
    </style>
</head>
<body class="min-vh-100">
    <div class="container py-4 panel-shell">
        @yield('content')
    </div>
</body>
</html>
