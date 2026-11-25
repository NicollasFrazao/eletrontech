{{-- The kind of phone, with its validation error. --}}
<label for="phone_type">{{ __('accounts.fields.phone_type') }}</label>
<select id="phone_type" name="phone_type" required>
    @foreach ($phoneTypes as $type)
        <option value="{{ $type->value }}" @selected($selected === $type->value)>{{ __('accounts.phone_types.'.$type->value) }}</option>
    @endforeach
</select>
@error('phone_type')
    <p class="error">{{ $message }}</p>
@enderror
