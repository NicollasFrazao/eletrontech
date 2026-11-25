<x-layouts.account :title="__('shop.title.custom')">
    <p>{{ __('shop.custom_intro') }}</p>
    <form method="POST" action="{{ route('shop.purchases') }}">
        @csrf
        <fieldset>
            <legend>{{ __('shop.tools') }}</legend>
            @foreach ($tools as $tool)
                <label>
                    <input type="checkbox" name="tools[]" value="{{ $tool->id->value }}" @checked(in_array($tool->id->value, old('tools', []), true))>
                    {{ $tool->name }}
                </label>
            @endforeach
        </fieldset>
        @error('tools')
            <p class="error">{{ $message }}</p>
        @enderror
        @include('shop.purchase-form')
    </form>
    <p><a href="{{ route('shop') }}">{{ __('shop.title.shop') }}</a></p>
</x-layouts.account>
