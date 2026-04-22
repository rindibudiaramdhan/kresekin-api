<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Seller Panel' }}</title>
    <style>
        :root { color-scheme: light; }
        body { font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; margin: 0; background: #f3f4f6; color: #111827; }
        a { color: #065f46; text-decoration: none; }
        .wrap { max-width: 1120px; margin: 0 auto; padding: 24px; }
        .card { background: #fff; border: 1px solid #e5e7eb; border-radius: 16px; padding: 20px; box-shadow: 0 1px 2px rgba(0,0,0,.04); }
        .grid { display: grid; gap: 16px; }
        .grid-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .grid-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .btn { display: inline-block; border: 0; background: #065f46; color: #fff; padding: 10px 14px; border-radius: 10px; cursor: pointer; }
        .btn-secondary { background: #e5e7eb; color: #111827; }
        .btn-danger { background: #b91c1c; }
        .input, select, textarea { width: 100%; box-sizing: border-box; border: 1px solid #d1d5db; border-radius: 10px; padding: 10px 12px; background: #fff; }
        label { display: block; margin-bottom: 6px; font-weight: 600; }
        .muted { color: #6b7280; }
        .topbar { display:flex; justify-content:space-between; align-items:center; gap:16px; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align:left; border-bottom: 1px solid #e5e7eb; padding: 12px 10px; vertical-align: top; }
        .alert { padding: 12px 14px; border-radius: 10px; margin-bottom: 16px; background: #ecfdf5; color:#065f46; border:1px solid #a7f3d0; }
        .error { color:#b91c1c; font-size: 14px; margin-top: 4px; }
        .actions { display:flex; gap:8px; flex-wrap: wrap; }
    </style>
</head>
<body>
    <div class="wrap">
        @yield('content')
    </div>
</body>
</html>
