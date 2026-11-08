<x-design::body>{{ __($check->whenSaid, ['ago' => trans_choice($check->ago->said, $check->ago->count)]) }}</x-design::body>
<x-design::strong>{{ __($check->endedSaid) }}</x-design::strong>

@if ($check->notBack !== [])
    <x-design::body>{{ __('restarts.not_back', ['services' => implode(', ', $check->notBack)]) }}</x-design::body>
@endif

<x-design::body>{{ __($check->tunnelSaid, ['said' => $check->tunnelWords]) }}</x-design::body>

@if ($check->port->said !== '')
    <x-design::body>{{ __($check->port->said, ['was' => (string) $check->port->was, 'now' => (string) $check->port->now]) }}</x-design::body>
@endif

@if ($check->notEveryClient)
    <x-design::note>{{ __('restarts.port.not_every_client') }}</x-design::note>
@endif

@if ($check->said !== '')
    {{-- The sentence the run raised, in the stack's words. --}}
    <x-design::note>{{ $check->said }}</x-design::note>
@endif
