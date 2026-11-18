@if ($last->exit->said !== '')
    <x-design::body>{{ __($last->exit->said, ['code' => $last->exit->code]) }}</x-design::body>
    <x-design::heading>{{ __('scheduled.log') }}</x-design::heading>

    @forelse ($last->log as $line)
        <x-design::verbatim>{{ $line }}</x-design::verbatim>
    @empty
        <x-design::body>{{ __('scheduled.no_log') }}</x-design::body>
    @endforelse
@endif
