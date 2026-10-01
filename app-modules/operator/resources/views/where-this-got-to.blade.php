<x-operator::stack-opens :title="$this->stack()->name()->shown()" :stacks="$this->stacksToChooseFrom()" :choosing="$this->choosingAStack" />

@if ($this->answer()->went->cameBack())
<x-operator::content>
    @if ($this->answer()->item === '')
        <x-design::title>{{ __('health.trace.nothing_named') }}</x-design::title>
    @elseif (! $this->answer()->followed)
        {{-- Nothing being watched for matched: nobody asked for it, which is
             an answer, and not the same one as a trace that could not be read. --}}
        <x-design::title>{{ __('health.trace.nothing_asked_for', ['item' => $this->answer()->item]) }}</x-design::title>
    @else
        <x-design::title>{{ __('health.trace.following', ['item' => $this->answer()->item]) }}</x-design::title>

        {{-- How sure, first: a guess drawn as fact is worse than a marked one. --}}
        @if ($this->answer()->isUncertain)
            <x-design::notice>
                <x-design::strong>{{ __($this->answer()->sureSaid) }}</x-design::strong>
            </x-design::notice>
        @else
            <x-design::note>{{ __($this->answer()->sureSaid) }}</x-design::note>
        @endif

        {{-- The stage is the stack's word, drawn as it came, with the plain
             sentence beside it rather than in its place. --}}
        @if ($this->answer()->furthest !== null)
            <x-design::card>
                <x-design::strong>{{ __('health.trace.furthest', ['stage' => $this->answer()->furthest->word]) }}</x-design::strong>
                <x-design::body>{{ __($this->answer()->furthest->said) }}</x-design::body>
                <x-operator::gloss :gloss="$this->gloss($this->answer()->furthest->word)" />
            </x-design::card>
        @endif

        @if ($this->answer()->stall !== '')
            <x-design::notice>
                <x-design::strong>{{ __('health.trace.stopped', ['why' => $this->answer()->stall]) }}</x-design::strong>
            </x-design::notice>
        @endif

        {{-- For a series, what is here and what is not, season by season: a
             series reads as imported the moment one episode lands. --}}
        @if ($this->answer()->here->series !== null)
            <x-design::heading>{{ __('health.trace.series_here', ['have' => $this->answer()->here->series->have, 'wanted' => $this->answer()->here->series->wanted]) }}</x-design::heading>
            @if ($this->answer()->here->series->unmonitored > 0)
                <x-design::note>{{ trans_choice('health.trace.nobody_asked_for', $this->answer()->here->series->unmonitored) }}</x-design::note>
            @endif
            @forelse ($this->answer()->here->series->seasons as $season)
                <x-design::card>
                    <x-design::strong>{{ __('health.trace.season', ['season' => $season->season, 'have' => $season->have, 'wanted' => $season->wanted]) }}</x-design::strong>
                    @forelse ($season->outstanding as $episode)
                        <x-design::note>{{ __('health.trace.episode', ['number' => $episode->number, 'title' => $episode->title, 'stage' => $episode->word]) }}</x-design::note>
                    @empty
                        <x-design::note>{{ __('health.trace.season_all_here') }}</x-design::note>
                    @endforelse
                </x-design::card>
            @empty
                <x-design::body>{{ __('health.trace.no_seasons') }}</x-design::body>
            @endforelse
        @endif

        <x-design::section :label="__('health.trace.the_way')">
            @forelse ($this->answer()->stages as $stage)
                <x-design::row
                    :headline="__('health.at_stage', ['stage' => $stage->word])"
                    :supporting="$stage->at === '' ? __('health.trace.recorded_untimed', ['service' => $stage->service]) : __('health.trace.recorded_at', ['service' => $stage->service, 'at' => $stage->at])"
                />
            @empty
                <x-design::row :headline="__('health.trace.no_stages')" />
            @endforelse
        </x-design::section>

        {{-- What has been tried, oldest first: a repeated attempt is a pattern. --}}
        <x-design::section :label="__('health.trace.tried')">
            @forelse ($this->answer()->history as $moment)
                <x-design::row :headline="__($moment->said)" :supporting="$moment->at" />
            @empty
                <x-design::row :headline="__('health.trace.nothing_tried')" />
            @endforelse
        </x-design::section>

        {{-- Where two services' views of it contradict, in the stack's words. --}}
        <x-design::section :label="__('health.trace.disagree')">
            @forelse ($this->answer()->disagreements as $disagreement)
                <x-design::row :headline="$disagreement" />
            @empty
                <x-design::row :headline="__('health.trace.agree')" />
            @endforelse
        </x-design::section>
    @endif

    <x-design::card>
        <native:outlined-text-input
            native:model="looking"
            label="{{ __('health.trace.search_label') }}"
            placeholder="{{ __('health.trace.search_placeholder') }}"
        />
        <x-design::action label="{{ __('health.trace.follow') }}" tap="follow()" tone="tonal" />
    </x-design::card>

    <x-design::action label="{{ __('health.ask_again') }}" tap="again()" tone="tonal" />
</x-operator::content>
@else
    <x-operator::what-stopped-the-reading
        :settings-would-not-open="$this->theSettingsWouldNotOpen"
        :went="$this->answer()->went"
        :sign-in-goes-to="$this->goes()->signIn()"
    />
@endif

<x-operator::screen-closes :goes="$this->goes()" :here="$this->itsTab()" />
