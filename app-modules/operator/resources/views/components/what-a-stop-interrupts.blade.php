@if (! $comingDown->found)
    <x-design::note>{{ __('health.coming.unknown') }}</x-design::note>
@elseif ($comingDown->offersWaiting())
    <x-operator::heading>{{ __('health.coming.heading') }}</x-operator::heading>
    @forelse ($comingDown->coming as $coming)
        <x-design::body>{{ __('health.coming.line', ['name' => $coming->name, 'progress' => $coming->progress, 'by' => __($coming->by)]) }}</x-design::body>
    @empty
        {{-- Unreachable while the branch above guards it: a list is drawn only where the stack named a download. --}}
    @endforelse
    <x-operator::note>{{ __('health.coming.interrupts') }}</x-operator::note>
@endif
