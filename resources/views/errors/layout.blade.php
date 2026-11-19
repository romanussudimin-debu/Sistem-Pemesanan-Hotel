{{-- resources/views/errors/layout.blade.php — halaman galat sederhana (Tahap 4, 4.1.3) --}}
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('code') · Nusa Stay Hotels</title>
    <style>body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;font-family:system-ui,sans-serif;background:#f8fafc;color:#1e293b;text-align:center;padding:1rem}h1{font-size:4rem;margin:0;color:#0f766e}a{display:inline-block;margin-top:1.5rem;padding:.6rem 1.2rem;border-radius:.5rem;background:#0f766e;color:#fff;text-decoration:none;font-weight:600}</style>
</head>
<body><div><h1>@yield('code')</h1><p style="font-size:1.25rem;font-weight:600">@yield('title')</p><p style="color:#64748b">@yield('message')</p>@hasSection('action') @yield('action') @else @include('errors.home-link') @endif</div></body>
</html>
