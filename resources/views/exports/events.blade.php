<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ __('agenda.export.title') }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11pt; }
        h1 { font-size: 16pt; }
        .event { margin-bottom: 12pt; }
        .when { color: #475569; }
    </style>
</head>
<body>
    <h1>{{ __('agenda.export.title') }}</h1>
    @forelse ($events as $event)
        <div class="event">
            <div class="when">{{ $event->details->startsAt->format('Y-m-d H:i') }}</div>
            <strong>{{ $event->details->title }}</strong>
            @if ($event->details->description !== '')
                <div>{{ $event->details->description }}</div>
            @endif
        </div>
    @empty
        <p>{{ __('agenda.empty') }}</p>
    @endforelse
</body>
</html>
