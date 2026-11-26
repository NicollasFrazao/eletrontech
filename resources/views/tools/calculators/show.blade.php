{{--
    One calculator (E9, #65): a form built from its input schema, posted to the calculation route, and the result
    lines. A quantity takes a number and the unit it was typed in; a choice, one of its options.
    A number field has no inputmode: iOS's decimal keypad has no minus sign, and a quantity may be negative (a gauge
    pressure, a height below the reference level). An old value is read only when it is a string, since a tampered
    request may post an array.
--}}
<x-layouts.account :title="__('calculators.names.'.$calculator->id)">
    <p><a href="{{ route('tools.calculators') }}">{{ __('calculators.back') }}</a></p>
    @guest
        <p><a href="{{ route('login') }}">{{ __('calculators.sign_in') }}</a></p>
    @endguest

    <form method="post" action="{{ route('tools.calculators.calculate', $calculator->id) }}">
        @csrf
        @error('inputs') <p class="error" role="alert">{{ $message }}</p> @enderror

        @foreach ($calculator->inputs as $input)
            @php($label = __('calculators.fields.'.$input->name))
            @php($typed = is_string($old = old($input->name, $values[$input->name] ?? '')) ? $old : '')
            <label for="{{ $input->name }}">
                {{ $label }}@unless ($input->isRequired) ({{ __('calculators.optional') }})@endunless
            </label>
            @if ($input->kind === 'choice')
                <select id="{{ $input->name }}" name="{{ $input->name }}" @required($input->isRequired) @error($input->name) aria-invalid="true" aria-describedby="{{ $input->name }}-error" @enderror>
                    @unless ($input->isRequired)
                        <option value="">{{ __('calculators.none') }}</option>
                    @endunless
                    @foreach ($input->accepts->options as $option)
                        <option value="{{ $option }}" @selected($typed === $option)>{{ __('calculators.options.'.$option) }}</option>
                    @endforeach
                </select>
            @else
                <input id="{{ $input->name }}" name="{{ $input->name }}" value="{{ $typed }}"
                    @required($input->isRequired) @error($input->name) aria-invalid="true" aria-describedby="{{ $input->name }}-error" @enderror>
            @endif
            @if ($input->accepts->defaultUnit !== null)
                @php($chosen = is_string($old = old('units.'.$input->name, $units[$input->name] ?? null)) ? $old : $input->accepts->defaultUnit->id)
                <select name="units[{{ $input->name }}]" aria-label="{{ __('calculators.unit', ['field' => $label]) }}"
                    @error('units.'.$input->name) aria-invalid="true" aria-describedby="{{ $input->name }}-unit-error" @enderror>
                    @foreach ($input->accepts->units as $unit)
                        <option value="{{ $unit->id }}" @selected($chosen === $unit->id)>{{ $unit->name }} ({{ $unit->symbol }})</option>
                    @endforeach
                </select>
            @endif
            @error($input->name) <p class="error" id="{{ $input->name }}-error">{{ $message }}</p> @enderror
            @error('units.'.$input->name) <p class="error" id="{{ $input->name }}-unit-error">{{ $message }}</p> @enderror
        @endforeach

        <button type="submit">{{ __('calculators.calculate') }}</button>
    </form>

    @isset($result)
        <section aria-labelledby="result">
            <h2 id="result">{{ __('calculators.result') }}</h2>
            @include('tools.calculators.lines', ['lines' => $result->values])
            @if ($result->intermediates !== [])
                <h3>{{ __('calculators.intermediates') }}</h3>
                @include('tools.calculators.lines', ['lines' => $result->intermediates])
            @endif
        </section>
    @endisset
</x-layouts.account>
