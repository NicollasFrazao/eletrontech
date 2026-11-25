<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} · {{ config('app.name') }}</title>
    <style>
        body { margin: 0; font-family: system-ui, sans-serif; background: #f8fafc; color: #0f172a; }
        body.night { background: #0f172a; color: #e2e8f0; }
        main { max-width: 40rem; margin: 0 auto; padding: 3rem 1rem; }
        a { color: #0284c7; }
        label { display: block; margin-top: .75rem; }
        input, select { display: block; width: 100%; max-width: 24rem; padding: .4rem; margin-top: .25rem; }
        input[type=checkbox] { display: inline; width: auto; }
        button { margin-top: 1rem; padding: .5rem 1rem; }
        section { margin-top: 2rem; }
        .status { padding: .5rem; background: #dcfce7; color: #14532d; }
        .error { color: #b91c1c; font-size: .9rem; }
    </style>
</head>
<body @class(['night' => $nightMode ?? false])>
    <main>
        <h1>{{ $title }}</h1>
        @if (session('status'))
            <p class="status" role="status">{{ session('status') }}</p>
        @endif
        {{ $slot }}
    </main>
</body>
</html>
