{{-- One row of what stopped moving. The kind leads, because it is what
     decides what to do; the name is the item, or the cause several items
     share, with how many it stands for rather than a row for each. What
     blocked it is the service's own words, drawn as they came, and how long
     is what makes stuck a sentence somebody can weigh. A row standing for one
     item leads to where that item got to. --}}
<x-operator::entry>
    <x-operator::note>{{ __($row->kindSaid) }}</x-operator::note>
    <x-operator::emphasis>{{ $row->name }}</x-operator::emphasis>

    @if ($row->items > 1)
        <x-design::body>{{ trans_choice('health.stopped_stands_for', $row->items) }}</x-design::body>
    @endif

    @if ($row->blocking !== '')
        <x-design::body>{{ __('health.stopped_blocking', ['words' => $row->blocking]) }}</x-design::body>
    @endif

    <x-operator::note>{{ trans_choice($row->heldSaid, $row->heldCount) }}</x-operator::note>

    @if ($trace !== '')
        <x-operator::quiet-action label="{{ __('health.trace.road_in', ['item' => $row->name]) }}" :goes="$trace" />
    @endif
</x-operator::entry>
