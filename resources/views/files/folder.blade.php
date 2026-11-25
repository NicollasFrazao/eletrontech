<x-layouts.account :title="__('files.title')">
    <nav aria-label="{{ __('files.title') }}">
        <a href="{{ route('files.root') }}">{{ __('files.root') }}</a>
        @foreach ($view->path as $crumb)
            / <a href="{{ route('files.folder', $crumb->id->value) }}">{{ $crumb->name->value }}</a>
        @endforeach
    </nav>
    <p>{{ __('files.usage', ['used' => number_format($view->usage / 1048576, 1).' MB', 'quota' => intdiv($quota, 1048576).' MB']) }}</p>

    @if ($view->contents->folders === [] && $view->contents->files === [])
        <p>{{ __('files.empty') }}</p>
    @endif
    <ul>
        @foreach ($view->contents->folders as $child)
            <li>
                📁 <a href="{{ route('files.folder', $child->id->value) }}">{{ $child->name->value }}</a>
                · <a href="{{ route('files.folders.download', $child->id->value) }}">{{ __('files.actions.zip') }}</a>
                <form method="POST" action="{{ route('files.folders.destroy', $child->id->value) }}" style="display: inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit">{{ __('files.actions.delete') }}</button>
                </form>
            </li>
        @endforeach
        @foreach ($view->contents->files as $file)
            <li>
                <a href="{{ route('files.download', $file->id->value) }}">{{ $file->entry->name->value }}</a>
                · <a href="{{ route('files.show', $file->id->value) }}">{{ __('files.actions.properties') }}</a>
                <form method="POST" action="{{ route('files.destroy', $file->id->value) }}" style="display: inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit">{{ __('files.actions.delete') }}</button>
                </form>
            </li>
        @endforeach
    </ul>

    <section>
        <form method="POST" action="{{ route('files.store') }}" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="folder" value="{{ $folder }}">
            <label for="file">{{ __('files.fields.file') }}</label>
            <input id="file" name="file" type="file" required>
            @error('file')
                <p class="error">{{ $message }}</p>
            @enderror
            <button type="submit">{{ __('files.actions.upload') }}</button>
        </form>
    </section>
    <section>
        <form method="POST" action="{{ route('files.folders.store') }}">
            @csrf
            <input type="hidden" name="parent" value="{{ $folder }}">
            @include('accounts.field', ['name' => 'name', 'label' => __('files.fields.name'), 'value' => old('name')])
            <button type="submit">{{ __('files.actions.create') }}</button>
        </form>
    </section>
</x-layouts.account>
