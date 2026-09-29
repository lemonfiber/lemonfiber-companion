<x-operator::screen-opens :title="$this->stack()->name()->shown()" />

@if ($this->answer()->went->cameBack())
<x-operator::content>
    @if ($this->answer()->isWorking)
        {{-- Running: the handle says only that, so this says only that, and
             how often it looks again. The lines arrive once, whole, when the
             walk has finished. --}}
        <x-design::standing
            :said="__('health.walkthrough.walking')"
            tone="working"
            :note="__($this->cadence()->saidOnTheScreen(), ['count' => $this->cadence()->seconds()])"
        />
        <x-design::body>{{ __('health.walkthrough.lines_when_done') }}</x-design::body>
        {{-- Leaving does not stop it, said while it runs because that is when
             somebody decides whether to wait for it. Where this device would
             not keep the handle, coming back will not find it, and that is
             said too. --}}
        @if ($this->willBeFoundAgain)
            <x-design::body>{{ __('health.walkthrough.leaving') }}</x-design::body>
        @else
            <x-design::body>{{ __('health.walkthrough.leaving_not_noted') }}</x-design::body>
        @endif
    @elseif ($this->answer()->hasEnded)
        {{-- Not a failure: the stack has no outcome to give, and the walk
             may well have worked. --}}
        <x-design::notice tone="unknown">
            <x-design::strong>{{ __('health.walkthrough.no_outcome') }}</x-design::strong>
            <x-design::body>{{ __('health.walkthrough.no_outcome_action') }}</x-design::body>
        </x-design::notice>
    @elseif ($this->answer()->record !== null)
        {{-- Already here comes first and in its own words: it is an outcome,
             and never a search that matched nothing. --}}
        @if ($this->answer()->record->alreadyHere)
            @if ($this->answer()->record->item !== '')
                <x-design::title>{{ __('health.walkthrough.already_here', ['item' => $this->answer()->record->item]) }}</x-design::title>
            @else
                <x-design::title>{{ __('health.walkthrough.already_here_unnamed') }}</x-design::title>
            @endif
        @elseif ($this->answer()->record->item !== '')
            <x-design::title>{{ __('health.walkthrough.walked', ['item' => $this->answer()->record->item]) }}</x-design::title>
        @endif

        <x-design::body>{{ __($this->answer()->record->stateSaid) }}</x-design::body>
        <x-design::note>{{ __($this->answer()->record->shapeSaid) }}</x-design::note>
        <x-design::note>{{ __('health.walkthrough.proves', ['proves' => $this->answer()->record->proves]) }}</x-design::note>

        {{-- Where it stopped, with the logs inline and the one thing to try:
             a fault report somebody has to go and research is one they
             abandon. --}}
        @if ($this->answer()->record->stopped->didStop)
            <x-design::notice tone="trouble">
                <x-design::strong>{{ __('health.walkthrough.stopped_at', ['step' => $this->gloss($this->answer()->record->stopped->step)->word]) }}</x-design::strong>
                <x-operator::gloss :gloss="$this->gloss($this->answer()->record->stopped->step)" />
                <x-design::body>{{ __($this->answer()->record->stopped->whySaid) }}</x-design::body>
                <x-design::body>{{ __('health.walkthrough.try', ['remedy' => $this->answer()->record->stopped->remedy]) }}</x-design::body>
            </x-design::notice>

            <x-design::card>
                <x-design::note>{{ __('health.walkthrough.logs') }}</x-design::note>
                @forelse ($this->answer()->record->stopped->logs as $log)
                    <x-design::verbatim>{{ $log }}</x-design::verbatim>
                @empty
                    <x-design::note>{{ __('health.walkthrough.no_logs') }}</x-design::note>
                @endforelse
            </x-design::card>
        @endif

        {{-- The record: every line as the stack said it, in the order it
             said them, drawn whole. A walk that ran while nobody was looking
             reads the same as one that was watched. Each step is the
             glossary's word for it where the glossary has one. --}}
        <x-design::heading>{{ __('health.walkthrough.record') }}</x-design::heading>
        @forelse ($this->answer()->record->lines as $line)
            <x-design::card>
                <x-design::note>{{ __('health.at_stage', ['stage' => $this->gloss($line->step)->word]) }}</x-design::note>
                <x-design::body>{{ $line->said }}</x-design::body>
                @if ($line->detail !== '')
                    <x-design::note>{{ $line->detail }}</x-design::note>
                @endif
                <x-operator::gloss :gloss="$this->gloss($line->step)" />
            </x-design::card>
        @empty
            <x-design::body>{{ __('health.walkthrough.said_nothing') }}</x-design::body>
        @endforelse

        @if ($this->answer()->record->linkSaid !== '')
            <x-design::body>{{ __($this->answer()->record->linkSaid) }}</x-design::body>
        @endif

        @if ($this->answer()->record->inBackground)
            <x-design::body>{{ __('health.walkthrough.in_background') }}</x-design::body>
        @endif

        {{-- Where it hands the operator on to, named, rather than a dead end
             at the moment somebody has just got what they came for. The step
             this app has a screen for is a row that goes there. --}}
        @if ($this->answer()->record->next !== [])
            <x-design::section :label="__('health.walkthrough.what_next')">
                @forelse ($this->answer()->record->next as $next)
                    @if ($next->leadsToWhereToWatch)
                        <x-design::row
                            :headline="__($next->said)"
                            :supporting="__('health.walkthrough.where_to_watch')"
                            :goes="$this->goes()->whoGetsIn()->clients()"
                            :answers-to="__($next->said)"
                        />
                    @else
                        <x-design::row :headline="__($next->said)" />
                    @endif
                @empty
                    <x-design::row :headline="__('health.walkthrough.nothing_next')" />
                @endforelse
            </x-design::section>
        @endif

        @if ($this->answer()->record->suggestions !== [])
            <x-design::heading>{{ __('health.walkthrough.suggested') }}</x-design::heading>
            @forelse ($this->answer()->record->suggestions as $suggestion)
                <x-design::link label="{{ __('health.walkthrough.walk_this', ['item' => $suggestion]) }}" tap="walkSuggested('{{ $loop->index }}')" />
            @empty
                <x-design::note>{{ __('health.walkthrough.nothing_suggested') }}</x-design::note>
            @endforelse
        @endif
    @else
        {{-- Nothing started here yet: the road a walk takes, each step the
             glossary's word for it, explained where the glossary carries it. --}}
        <x-design::title>{{ __('health.walkthrough.offer') }}</x-design::title>
        <x-design::body>{{ __('health.walkthrough.offer_explained') }}</x-design::body>
        <x-design::heading>{{ __('health.walkthrough.road') }}</x-design::heading>
        @forelse ($this->answer()->road as $step)
            <x-design::card>
                <x-design::strong>{{ __('health.at_stage', ['stage' => $this->gloss($step)->word]) }}</x-design::strong>
                <x-operator::gloss :gloss="$this->gloss($step)" />
            </x-design::card>
        @empty
            <x-design::body>{{ __('health.walkthrough.no_road') }}</x-design::body>
        @endforelse
    @endif

    @unless ($this->answer()->isWorking)
        <x-design::card>
            <native:outlined-text-input
                native:model="looking"
                label="{{ __('health.walkthrough.walk_label') }}"
                placeholder="{{ __('health.walkthrough.walk_placeholder') }}"
            />
            <x-design::note>{{ __('health.walkthrough.blank_picks') }}</x-design::note>
        </x-design::card>
        <x-design::action label="{{ __('health.walkthrough.walk') }}" tap="walk()" />
    @endunless

    @if ($this->answer()->wasStarted)
        <x-design::action label="{{ __('health.ask_again') }}" tap="again()" tone="tonal" />
    @endif
</x-operator::content>
@else
    <x-operator::what-stopped-the-reading
        :went="$this->answer()->went"
        :sign-in-goes-to="$this->goes()->signIn()"
    />
@endif

<x-operator::screen-closes :goes="$this->goes()" here="health" />
