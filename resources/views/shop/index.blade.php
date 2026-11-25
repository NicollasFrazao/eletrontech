<x-layouts.account :title="__('shop.title.shop')">
    <p>{{ __('shop.intro') }}</p>
    @foreach ($packages as $package)
        <section>
            <h2>{{ $package->name }}</h2>
            <p>{{ implode(', ', array_map(fn ($tool) => $tool->name, $package->tools)) }}</p>
            <p><a href="{{ route('shop.package', $package->id) }}">{{ __('shop.see_more') }}</a></p>
        </section>
    @endforeach
    <section>
        <h2>{{ __('shop.title.custom') }}</h2>
        <p>{{ __('shop.custom_intro') }}</p>
        <p><a href="{{ route('shop.custom') }}">{{ __('shop.actions.custom') }}</a></p>
    </section>
    <p><a href="{{ route('licence') }}">{{ __('shop.title.licence') }}</a></p>
</x-layouts.account>
