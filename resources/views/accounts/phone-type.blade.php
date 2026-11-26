{{-- The kind of phone, with its validation error tied to the field. --}}
<label for="phone_type">{{ __('accounts.fields.phone_type') }}</label>
<select id="phone_type" name="phone_type" required @error('phone_type') aria-invalid="true" aria-describedby="phone_type-error" @enderror>
    @foreach ($phoneTypes as $type)
        <option value="{{ $type->value }}" @selected($selected === $type->value)>{{ __('accounts.phone_types.'.$type->value) }}</option>
    @endforeach
</select>
@error('phone_type')
    <p class="error" id="phone_type-error">{{ $message }}</p>
@enderror
