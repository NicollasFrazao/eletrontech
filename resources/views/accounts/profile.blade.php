@php
    $account = $profile->account;
    $details = $account->details();
    $preferences = $account->profile()->preferences;
@endphp
<x-layouts.account :title="__('accounts.title.profile')" :night-mode="$preferences->isNightMode">
    <p><a href="{{ route('licence') }}">{{ __('shop.title.licence') }}</a> · <a href="{{ route('shop') }}">{{ __('shop.title.shop') }}</a> · <a href="{{ route('agenda') }}">{{ __('agenda.title') }}</a> · <a href="{{ route('files.root') }}">{{ __('files.title') }}</a></p>
    <section>
        <h2>{{ __('accounts.sections.images') }}</h2>
        @foreach (\App\Domain\Account\ImageKind::cases() as $kind)
            @if ($account->profile()->imagePath($kind) !== null)
                <img src="{{ route('profile.images.show', $kind) }}" alt="{{ __('accounts.fields.'.$kind->value) }}" style="max-width: 100%">
            @endif
            <form method="POST" action="{{ route('profile.images.store', $kind) }}" enctype="multipart/form-data">
                @csrf
                <label for="image-{{ $kind->value }}">{{ __('accounts.fields.'.$kind->value) }}</label>
                <input id="image-{{ $kind->value }}" name="image" type="file" accept="image/jpeg,image/png,image/webp" required>
                <button type="submit">{{ __('accounts.actions.upload') }}</button>
            </form>
        @endforeach
        @error('image')
            <p class="error">{{ $message }}</p>
        @enderror
    </section>

    <section>
        <h2>{{ __('accounts.sections.details') }}</h2>
        <p>{{ __('accounts.fields.email') }}: {{ $account->access()->email->value }}</p>
        <p>{{ __('accounts.fields.cpf') }}: {{ $details->cpf->digits }}</p>
        <form method="POST" action="{{ route('profile.details') }}">
            @csrf
            @method('PUT')
            @include('accounts.field', ['name' => 'name', 'label' => __('accounts.fields.name'), 'value' => old('name', $details->name->value), 'autocomplete' => 'name'])
            @include('accounts.field', ['name' => 'phone_number', 'label' => __('accounts.fields.phone_number'), 'value' => old('phone_number', $details->phone->number->digits), 'type' => 'tel', 'autocomplete' => 'tel'])
            @include('accounts.phone-type', ['selected' => old('phone_type', $details->phone->type->value)])
            <button type="submit">{{ __('accounts.actions.save') }}</button>
        </form>
    </section>

    <section>
        <h2>{{ __('accounts.sections.password') }}</h2>
        <form method="POST" action="{{ route('profile.password') }}">
            @csrf
            @method('PUT')
            @include('accounts.field', ['name' => 'current_password', 'label' => __('accounts.fields.current_password'), 'type' => 'password', 'autocomplete' => 'current-password'])
            @include('accounts.field', ['name' => 'password', 'label' => __('accounts.fields.new_password'), 'type' => 'password', 'autocomplete' => 'new-password'])
            @include('accounts.field', ['name' => 'password_confirmation', 'label' => __('accounts.fields.password_confirmation'), 'type' => 'password', 'autocomplete' => 'new-password'])
            <button type="submit">{{ __('accounts.actions.save') }}</button>
        </form>
    </section>

    <section>
        <h2>{{ __('accounts.sections.preferences') }}</h2>
        <form method="POST" action="{{ route('profile.preferences') }}">
            @csrf
            @method('PUT')
            <label><input type="checkbox" name="is_night_mode" value="1" @checked($preferences->isNightMode)> {{ __('accounts.fields.is_night_mode') }}</label>
            <label><input type="checkbox" name="shows_tutorial" value="1" @checked($preferences->showsTutorial)> {{ __('accounts.fields.shows_tutorial') }}</label>
            <button type="submit">{{ __('accounts.actions.save') }}</button>
        </form>
    </section>

    <section>
        <h2>{{ __('accounts.sections.activity') }}</h2>
        <ul>
            @forelse ($profile->activity as $activity)
                <li>{{ __('accounts.activity.'.$activity->type->value) }}, {{ $activity->occurredAt->format('Y-m-d H:i') }}</li>
            @empty
                <li>{{ __('accounts.no_activity') }}</li>
            @endforelse
        </ul>
    </section>

    <section>
        <h2>{{ __('accounts.sections.deactivate') }}</h2>
        <p>{{ __('accounts.deactivate_notice') }}</p>
        <form method="POST" action="{{ route('profile.deactivate') }}">
            @csrf
            @include('accounts.field', ['name' => 'deactivation_password', 'label' => __('accounts.fields.password'), 'type' => 'password', 'autocomplete' => 'current-password'])
            <button type="submit">{{ __('accounts.actions.deactivate') }}</button>
        </form>
    </section>

    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit">{{ __('accounts.actions.logout') }}</button>
    </form>
</x-layouts.account>
