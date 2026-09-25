<x-operator::screen-opens :title="$this->stack()->name()->shown()" />

@if ($this->answer()->went->cameBack())
<x-operator::content>
    @if ($this->answer()->item === '')
        <x-operator::emphasis>{{ __('health.trace.nothing_named') }}</x-operator::emphasis>
    @elseif (! $this->answer()->followed)
        {{-- Nothing being watched for matched: nobody asked for it, which is
             an answer, and not the same one as a trace that could not be read. --}}
        <x-operator::emphasis>{{ __('health.trace.nothing_asked_for', ['item' => $this->answer()->item]) }}</x-operator::emphasis>
    @else
        <x-operator::emphasis>{{ __('health.trace.following', ['item' => $this->answer()->item]) }}</x-operator::emphasis>

        {{-- How sure, first: a guess drawn as fact is worse than a marked one. --}}
        @if ($this->answer()->isUncertain)
            <x-operator::emphasis>{{ __($this->answer()->sureSaid) }}</x-operator::emphasis>
        @else
            <x-operator::note>{{ __($this->answer()->sureSaid) }}</x-operator::note>
        @endif

        {{-- The stage is the stack's word, drawn as it came, with the plain
             sentence beside it rather than in its place. --}}
        @if ($this->answer()->furthest !== null)
            <native:text>{{ __('health.trace.furthest', ['stage' => $this->answer()->furthest->word]) }}</native:text>
            <native:text>{{ __($this->answer()->furthest->said) }}</native:text>
            <x-operator::gloss :gloss="$this->gloss($this->answer()->furthest->word)" />
        @endif

        @if ($this->answer()->stall !== '')
            <x-operator::emphasis>{{ __('health.trace.stopped', ['why' => $this->answer()->stall]) }}</x-operator::emphasis>
        @endif

        {{-- For a series, what is here and what is not, season by season: a
             series reads as imported the moment one episode lands. --}}
        @if ($this->answer()->here->series !== null)
            <x-operator::emphasis>{{ __('health.trace.series_here', ['have' => $this->answer()->here->series->have, 'wanted' => $this->answer()->here->series->wanted]) }}</x-operator::emphasis>
            @if ($this->answer()->here->series->unmonitored > 0)
                <x-operator::note>{{ trans_choice('health.trace.nobody_asked_for', $this->answer()->here->series->unmonitored) }}</x-operator::note>
            @endif
            @forelse ($this->answer()->here->series->seasons as $season)
                <x-operator::entry>
                    <native:text>{{ __('health.trace.season', ['season' => $season->season, 'have' => $season->have, 'wanted' => $season->wanted]) }}</native:text>
                    @forelse ($season->outstanding as $episode)
                        <x-operator::note>{{ __('health.trace.episode', ['number' => $episode->number, 'title' => $episode->title, 'stage' => $episode->word]) }}</x-operator::note>
                    @empty
                        <x-operator::note>{{ __('health.trace.season_all_here') }}</x-operator::note>
                    @endforelse
                </x-operator::entry>
            @empty
                <x-operator::note>{{ __('health.trace.no_seasons') }}</x-operator::note>
            @endforelse
        @endif

        <x-operator::emphasis>{{ __('health.trace.the_way') }}</x-operator::emphasis>
        @forelse ($this->answer()->stages as $stage)
            <x-operator::entry>
                <native:text>{{ __('health.at_stage', ['stage' => $stage->word]) }}</native:text>
                @if ($stage->at !== '')
                    <x-operator::note>{{ __('health.trace.recorded_at', ['service' => $stage->service, 'at' => $stage->at]) }}</x-operator::note>
                @else
                    <x-operator::note>{{ __('health.trace.recorded_untimed', ['service' => $stage->service]) }}</x-operator::note>
                @endif
            </x-operator::entry>
        @empty
            <x-operator::note>{{ __('health.trace.no_stages') }}</x-operator::note>
        @endforelse

        {{-- What has been tried, oldest first: a repeated attempt is a pattern. --}}
        <x-operator::emphasis>{{ __('health.trace.tried') }}</x-operator::emphasis>
        @forelse ($this->answer()->history as $moment)
            <x-operator::entry>
                <native:text>{{ __($moment->said) }}</native:text>
                <x-operator::note>{{ $moment->at }}</x-operator::note>
            </x-operator::entry>
        @empty
            <x-operator::note>{{ __('health.trace.nothing_tried') }}</x-operator::note>
        @endforelse

        {{-- Where two services' views of it contradict, in the stack's words. --}}
        <x-operator::emphasis>{{ __('health.trace.disagree') }}</x-operator::emphasis>
        @forelse ($this->answer()->disagreements as $disagreement)
            <x-operator::note>{{ $disagreement }}</x-operator::note>
        @empty
            <x-operator::note>{{ __('health.trace.agree') }}</x-operator::note>
        @endforelse
    @endif

    <native:outlined-text-input
        native:model="looking"
        label="{{ __('health.trace.search_label') }}"
        placeholder="{{ __('health.trace.search_placeholder') }}"
    />
    <x-operator::quiet-action label="{{ __('health.trace.follow') }}" tap="follow()" />

    <x-operator::action label="{{ __('health.ask_again') }}" tap="again()" />
</x-operator::content>
@else
    <x-operator::what-stopped-the-reading
        :went="$this->answer()->went"
        :sign-in-goes-to="$this->goes()->signIn()"
    />
@endif

<x-operator::screen-closes :goes="$this->goes()" here="health" />
