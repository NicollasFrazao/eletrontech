{{-- One labelled input with its validation error. --}}
<label for="{{ $name }}">{{ $label }}</label>
<input id="{{ $name }}" name="{{ $name }}" type="{{ $type ?? 'text' }}" value="{{ $value ?? '' }}"
       autocomplete="{{ $autocomplete ?? 'off' }}" @if ($required ?? true) required @endif>
@error($name)
    <p class="error">{{ $message }}</p>
@enderror
