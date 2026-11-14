<x-design::card>
    <x-design::strong>{{ __($backEnd->kindSaid) }}</x-design::strong>
    <x-design::verbatim>{{ $backEnd->where }}</x-design::verbatim>
    <x-design::note>{{ __($backEnd->chosen->said, ['member' => $backEnd->chosen->filling]) }}</x-design::note>

    @if ($backEnd->hosted->host !== '')
        <x-design::body>{{ __('alerts.hosted_at', ['host' => $backEnd->hosted->host]) }}</x-design::body>
        @forelse ($backEnd->hosted->leaves as $leaves)
            <x-design::note>{{ $leaves }}</x-design::note>
        @empty
            <x-design::note>{{ __('alerts.hosted.sends_nothing') }}</x-design::note>
        @endforelse
    @endif

    <x-design::body>{{ __($backEnd->proof->said, ['ago' => trans_choice($backEnd->proof->ago->said, $backEnd->proof->ago->count), 'why' => $backEnd->proof->filling]) }}</x-design::body>
    <x-design::body>{{ __($backEnd->last->ended->said, ['ago' => trans_choice($backEnd->last->ended->ago->said, $backEnd->last->ended->ago->count), 'reason' => $backEnd->last->ended->filling]) }}</x-design::body>

    @if ($backEnd->failures !== [])
        <x-design::note>{{ trans_choice('alerts.failed_since', count($backEnd->failures)) }}</x-design::note>
    @endif

    @if ($backEnd->retry->said !== '')
        <x-design::note>{{ __($backEnd->retry->said, ['in' => trans_choice($backEnd->retry->in->said, $backEnd->retry->in->count), 'attempt' => $backEnd->retry->attempt, 'of' => $backEnd->retry->of]) }}</x-design::note>
    @endif

    @if ($opens)
        <x-design::link :label="__('alerts.open', ['where' => $backEnd->where])" :goes="$backEnd->goes" />
    @endif
</x-design::card>
