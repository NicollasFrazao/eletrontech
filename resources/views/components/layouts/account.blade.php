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
        h1, h2, p, li, dd, nav { overflow-wrap: anywhere; }
        /*
         * Phones (E9, #62): one column, full-width controls and links with a 44 px touch target, no horizontal
         * scroll. Every page of the user area and the shop uses this layout, so the rules live here once.
         */
        @media (max-width: 40rem) {
            main { padding: 1.5rem 1rem; }
            h1 { font-size: 1.5rem; }
            ul { padding-left: 1.25rem; }
            fieldset { min-width: 0; margin: 1rem 0 0; }
            input, select, textarea, button { box-sizing: border-box; display: block; width: 100%; max-width: none; min-height: 44px; font: inherit; }
            input[type=checkbox] { display: inline-block; width: 1.5rem; min-height: 1.5rem; height: 1.5rem; margin: 0; flex: none; }
            label:has(> input[type=checkbox]) { display: flex; align-items: center; gap: .5rem; min-height: 44px; }
            main a { display: inline-flex; align-items: center; min-height: 44px; }
        }
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
