<x-layouts.account :title="$file->entry->name->value">
    <dl>
        <dt>{{ __('files.properties.name') }}</dt><dd>{{ $file->entry->name->value }}</dd>
        <dt>{{ __('files.properties.type') }}</dt><dd>{{ strtoupper($file->entry->type->value) }}</dd>
        <dt>{{ __('files.properties.size') }}</dt><dd>{{ __('files.properties.bytes', ['bytes' => number_format($file->size)]) }}</dd>
        <dt>{{ __('files.properties.created') }}</dt><dd>{{ $file->createdAt->format('Y-m-d H:i') }}</dd>
    </dl>
    <p><a href="{{ route('files.download', $file->id->value) }}">{{ __('files.actions.download') }}</a></p>
    <p><a href="{{ $file->entry->folder === null ? route('files.root') : route('files.folder', $file->entry->folder->value) }}">{{ __('files.title') }}</a></p>
</x-layouts.account>
