<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>{{ $title ?? 'Dashboard' }} - {{ config('app.name', 'Kresek.in') }}</title>
    <style>
        body {
            min-height: 100vh;
            margin: 0;
            background: #f8fafc;
        }
    </style>
</head>
<body></body>
</html>
