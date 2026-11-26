<x-layouts.account :title="__('feedback.title')">
    <p>{{ __('feedback.intro') }}</p>
    <form method="POST" action="{{ route('feedback.store') }}">
        @csrf
        <label for="subject">{{ __('feedback.fields.subject') }}</label>
        <select id="subject" name="subject" required @error('subject') aria-invalid="true" aria-describedby="subject-error" @enderror>
            <option value="">{{ __('feedback.choose') }}</option>
            @foreach ($subjects as $subject)
                <option value="{{ $subject->value }}" @selected(old('subject') === $subject->value)>{{ __('feedback.subjects.'.$subject->value) }}</option>
            @endforeach
        </select>
        @error('subject')
            <p class="error" id="subject-error">{{ $message }}</p>
        @enderror
        <label for="message">{{ __('feedback.fields.message') }}</label>
        <textarea id="message" name="message" maxlength="500" rows="6" required @error('message') aria-invalid="true" aria-describedby="message-error" @enderror>{{ old('message') }}</textarea>
        @error('message')
            <p class="error" id="message-error">{{ $message }}</p>
        @enderror
        <button type="submit">{{ __('feedback.actions.send') }}</button>
    </form>
    <p><a href="{{ route('profile') }}">{{ __('accounts.title.profile') }}</a></p>
</x-layouts.account>
