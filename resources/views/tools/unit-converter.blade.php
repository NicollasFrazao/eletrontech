{{-- The unit converter. A minimal form until the interface is designed in E9 (#12). --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('unit-converter.title') }} · {{ config('app.name') }}</title>
    <style>
        body { margin: 0; font-family: system-ui, sans-serif; background: #0f172a; color: #e2e8f0; }
        main { max-width: 40rem; margin: 0 auto; padding: 4rem 1rem; }
        /* Accessibility (E9, #64): a 3 px focus ring for keyboard users, 8:1 against the background. */
        :focus-visible { outline: 3px solid #38bdf8; outline-offset: 2px; }
        label { display: block; margin-top: 1rem; }
        input, select, button { font: inherit; padding: 0.4rem; margin-top: 0.25rem; }
        .error { color: #fca5a5; }
        output { display: block; margin-top: 2rem; font-size: 1.5rem; overflow-wrap: anywhere; }
        /* Phones (E9, #61): one column, full-width controls with a 44 px touch target, no horizontal scroll. */
        @media (max-width: 40rem) {
            main { padding: 1.5rem 1rem; }
            h1 { font-size: 1.5rem; }
            input, select, button { box-sizing: border-box; display: block; width: 100%; min-height: 44px; }
            output { font-size: 1.25rem; line-height: 1.4; }
        }
    </style>
</head>
<body>
<main>
    <h1>{{ __('unit-converter.title') }}</h1>
    <p>{{ __('unit-converter.intro') }}</p>

    <form method="post" action="{{ route('tools.unit-converter.convert') }}">
        @csrf
        <label for="value">{{ __('unit-converter.value') }}</label>
        <input id="value" name="value" inputmode="decimal" required value="{{ old('value', $result?->from->value) }}"
               @error('value') aria-invalid="true" aria-describedby="value-error" @enderror>
        @error('value') <p class="error" id="value-error">{{ $message }}</p> @enderror

        @foreach (['from' => $result?->from->unit->id, 'to' => $result?->to->unit->id] as $field => $selected)
            <label for="{{ $field }}">{{ __('unit-converter.'.$field) }}</label>
            <select id="{{ $field }}" name="{{ $field }}" required
                    @error($field) aria-invalid="true" aria-describedby="{{ $field }}-error" @enderror>
                @foreach ($groups as $group)
                    <optgroup label="{{ __('unit-converter.dimensions.'.$group->dimension) }}">
                        @foreach ($group->units as $unit)
                            <option value="{{ $unit->id }}" @selected(old($field, $selected) === $unit->id)>{{ $unit->name }} ({{ $unit->symbol }})</option>
                        @endforeach
                    </optgroup>
                @endforeach
            </select>
            @error($field) <p class="error" id="{{ $field }}-error">{{ $message }}</p> @enderror
        @endforeach

        <p><button type="submit">{{ __('unit-converter.convert') }}</button></p>
    </form>

    @isset($result)
        <output aria-label="{{ __('unit-converter.result') }}">
            {{ $result->from->value }} {{ $result->from->unit->symbol }} {{ $result->isExact ? '=' : '≈' }} {{ $result->to->value }} {{ $result->to->unit->symbol }}
        </output>
        @unless ($result->isExact)
            <p>({{ __('unit-converter.rounded') }})</p>
        @endunless
    @endisset
</main>
</body>
</html>
