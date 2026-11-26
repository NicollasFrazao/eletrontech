{{-- The fields of an event. --}}
@include('accounts.field', ['name' => 'title', 'label' => __('agenda.fields.title'), 'value' => old('title', $event?->details->title)])
@include('accounts.field', ['name' => 'starts_at', 'label' => __('agenda.fields.starts_at'), 'type' => 'datetime-local', 'value' => old('starts_at', $event?->details->startsAt->format('Y-m-d\TH:i'))])
<label for="description">{{ __('agenda.fields.description') }}</label>
<textarea id="description" name="description" maxlength="500" rows="3">{{ old('description', $event?->details->description) }}</textarea>
@error('description')
    <p class="error">{{ $message }}</p>
@enderror
