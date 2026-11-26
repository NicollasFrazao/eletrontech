{{-- Result lines: a rounded value is marked, and a text that is an option, such as a band colour, shows its name. --}}
<dl>
    @foreach ($lines as $line)
        <dt>{{ __('calculators.fields.'.$line->name) }}</dt>
        <dd>
            @if ($line->unit === null && Lang::has('calculators.options.'.$line->value))
                {{ __('calculators.options.'.$line->value) }}
            @else
                {{ $line->isExact ? '' : '≈ ' }}{{ $line->value }}@if ($line->unit !== null) {{ $line->unit->symbol }}@endif
                @unless ($line->isExact) ({{ __('calculators.rounded') }}) @endunless
            @endif
        </dd>
    @endforeach
</dl>
