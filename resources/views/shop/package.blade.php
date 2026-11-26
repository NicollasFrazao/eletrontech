<x-layouts.account :title="$package->name">
    <h2>{{ __('shop.tools') }}</h2>
    <ul>
        @foreach ($package->tools as $tool)
            <li>{{ $tool->name }}</li>
        @endforeach
    </ul>
    <form method="POST" action="{{ route('shop.purchases') }}">
        @csrf
        <input type="hidden" name="package_id" value="{{ $package->id }}">
        @error('package_id')
            <p class="error" role="alert">{{ $message }}</p>
        @enderror
        @include('shop.purchase-form')
    </form>
    <p><a href="{{ route('shop') }}">{{ __('shop.title.shop') }}</a></p>
</x-layouts.account>
