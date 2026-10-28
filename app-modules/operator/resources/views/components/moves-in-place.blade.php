{{-- Moves in place, one a row: the stack's own service and the plugin that
     takes, or would take, its place, with why where a move was not taken. --}}
@forelse ($moves as $move)
    <x-operator::entry>
        @if ($loop->first)
            <x-design::heading>{{ __('updates.moves.heading') }}</x-design::heading>
        @endif

        <x-design::body>{{ __($move->said, ['plugin' => $move->plugin, 'service' => $move->service]) }}</x-design::body>

        @if ($move->owesAReason && $move->because !== '')
            <x-operator::note>{{ $move->because }}</x-operator::note>
        @elseif ($move->owesAReason)
            <x-operator::note>{{ __('updates.moves.why_unsaid') }}</x-operator::note>
        @endif

        @if ($loop->last && $after !== null)
            <x-operator::note>{{ $after }}</x-operator::note>
        @endif
    </x-operator::entry>
@empty
@endforelse
