{{-- The calculators by group (E9, #65); each one opens its own page. --}}
<x-layouts.account :title="__('calculators.title')">
    <p>{{ __('calculators.intro') }}</p>
    @foreach ($groups as $group => $calculators)
        <section aria-labelledby="group-{{ $group }}">
            <h2 id="group-{{ $group }}">{{ __('calculators.groups.'.$group) }}</h2>
            <ul>
                @foreach ($calculators as $calculator)
                    <li><a href="{{ route('tools.calculators.show', $calculator->id) }}">{{ __('calculators.names.'.$calculator->id) }}</a></li>
                @endforeach
            </ul>
        </section>
    @endforeach
    <p><a href="{{ route('tools.unit-converter') }}">{{ __('unit-converter.title') }}</a></p>
</x-layouts.account>
