<x-layouts.account :title="__('accounts.title.verify')">
    <p>{{ __('accounts.verify_notice') }}</p>
    <form method="POST" action="{{ route('verification.send') }}">
        @csrf
        <button type="submit">{{ __('accounts.actions.resend') }}</button>
    </form>
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit">{{ __('accounts.actions.logout') }}</button>
    </form>
</x-layouts.account>
