{{-- One labelled input with its validation error, tied to the input so a screen reader announces it with the field. --}}
<label for="{{ $name }}">{{ $label }}</label>
<input id="{{ $name }}" name="{{ $name }}" type="{{ $type ?? 'text' }}" value="{{ $value ?? '' }}"
       autocomplete="{{ $autocomplete ?? 'off' }}" @if ($required ?? true) required @endif
       @error($name) aria-invalid="true" aria-describedby="{{ $name }}-error" @enderror>
@error($name)
    <p class="error" id="{{ $name }}-error">{{ $message }}</p>
@enderror
