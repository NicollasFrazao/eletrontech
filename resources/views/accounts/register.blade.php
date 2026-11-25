<x-layouts.account :title="__('accounts.title.register')">
    <form method="POST" action="{{ route('register') }}">
        @csrf
        @include('accounts.field', ['name' => 'name', 'label' => __('accounts.fields.name'), 'value' => old('name'), 'autocomplete' => 'name'])
        @include('accounts.field', ['name' => 'cpf', 'label' => __('accounts.fields.cpf'), 'value' => old('cpf')])
        @include('accounts.field', ['name' => 'phone_number', 'label' => __('accounts.fields.phone_number'), 'value' => old('phone_number'), 'type' => 'tel', 'autocomplete' => 'tel'])
        @include('accounts.phone-type', ['selected' => old('phone_type', 'mobile')])
        @include('accounts.field', ['name' => 'email', 'label' => __('accounts.fields.email'), 'value' => old('email'), 'type' => 'email', 'autocomplete' => 'email'])
        @include('accounts.field', ['name' => 'password', 'label' => __('accounts.fields.password'), 'type' => 'password', 'autocomplete' => 'new-password'])
        @include('accounts.field', ['name' => 'password_confirmation', 'label' => __('accounts.fields.password_confirmation'), 'type' => 'password', 'autocomplete' => 'new-password'])
        <button type="submit">{{ __('accounts.actions.register') }}</button>
    </form>
    <p><a href="{{ route('login') }}">{{ __('accounts.links.have_account') }}</a></p>
</x-layouts.account>
