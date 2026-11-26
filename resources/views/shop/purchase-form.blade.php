{{-- The days and the password that confirm a purchase. --}}
<label for="days">{{ __('shop.fields.days', ['min' => $minDays, 'max' => $maxDays]) }}</label>
<input id="days" name="days" type="number" min="{{ $minDays }}" max="{{ $maxDays }}" value="{{ old('days', $minDays) }}" required
       @error('days') aria-invalid="true" aria-describedby="days-error" @enderror>
@error('days')
    <p class="error" id="days-error">{{ $message }}</p>
@enderror
@include('accounts.field', ['name' => 'purchase_password', 'label' => __('shop.fields.purchase_password'), 'type' => 'password', 'autocomplete' => 'current-password'])
<button type="submit">{{ __('shop.actions.buy') }}</button>
