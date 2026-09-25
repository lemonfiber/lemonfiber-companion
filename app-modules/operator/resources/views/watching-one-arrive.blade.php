<x-operator::screen-opens :title="$this->stack()->name()->shown()" />

@if ($this->answer()->went->cameBack())
<x-operator::content>
    @if ($this->answer()->isWorking)
        {{-- Running: the handle says only that, so this says only that, and
             how often it looks again. The lines arrive once, whole, when the
             walk has finished. --}}
        <x-operator::emphasis>{{ __('health.walkthrough.walking') }}</x-operator::emphasis>
        <native:text>{{ __('health.walkthrough.lines_when_done') }}</native:text>
        <x-operator::note>
            {{ __($this->cadence()->saidOnTheScreen(), ['count' => $this->cadence()->seconds()]) }}
        </x-operator::note>
    @elseif ($this->answer()->hasEnded)
        {{-- Not a failure: the stack has no outcome to give, and the walk
             may well have worked. --}}
        <native:text>{{ __('health.walkthrough.no_outcome') }}</native:text>
        <x-operator::note>{{ __('health.walkthrough.no_outcome_action') }}</x-operator::note>
    @elseif ($this->answer()->record !== null)
        {{-- Already here comes first and in its own words: it is an outcome,
             and never a search that matched nothing. --}}
        @if ($this->answer()->record->alreadyHere)
            @if ($this->answer()->record->item !== '')
                <x-operator::emphasis>{{ __('health.walkthrough.already_here', ['item' => $this->answer()->record->item]) }}</x-operator::emphasis>
            @else
                <x-operator::emphasis>{{ __('health.walkthrough.already_here_unnamed') }}</x-operator::emphasis>
            @endif
        @elseif ($this->answer()->record->item !== '')
            <x-operator::emphasis>{{ __('health.walkthrough.walked', ['item' => $this->answer()->record->item]) }}</x-operator::emphasis>
        @endif

        <native:text>{{ __($this->answer()->record->stateSaid) }}</native:text>
        <x-operator::note>{{ __($this->answer()->record->shapeSaid) }}</x-operator::note>
        <x-operator::note>{{ __('health.walkthrough.proves', ['proves' => $this->answer()->record->proves]) }}</x-operator::note>

        {{-- Where it stopped, with the logs inline and the one thing to try:
             a fault report somebody has to go and research is one they
             abandon. --}}
        @if ($this->answer()->record->stopped->didStop)
            <x-operator::emphasis>{{ __('health.walkthrough.stopped_at', ['step' => $this->gloss($this->answer()->record->stopped->step)->word]) }}</x-operator::emphasis>
            <x-operator::gloss :gloss="$this->gloss($this->answer()->record->stopped->step)" />
            <native:text>{{ __($this->answer()->record->stopped->whySaid) }}</native:text>
            <native:text>{{ __('health.walkthrough.try', ['remedy' => $this->answer()->record->stopped->remedy]) }}</native:text>
            <x-operator::note>{{ __('health.walkthrough.logs') }}</x-operator::note>
            @forelse ($this->answer()->record->stopped->logs as $log)
                <x-operator::note>{{ $log }}</x-operator::note>
            @empty
                <x-operator::note>{{ __('health.walkthrough.no_logs') }}</x-operator::note>
            @endforelse
        @endif

        {{-- The record: every line as the stack said it, in the order it
             said them, drawn whole. A walk that ran while nobody was looking
             reads the same as one that was watched. Each step is the
             glossary's word for it where the glossary has one. --}}
        <x-operator::emphasis>{{ __('health.walkthrough.record') }}</x-operator::emphasis>
        @forelse ($this->answer()->record->lines as $line)
            <x-operator::entry>
                <native:text>{{ __('health.at_stage', ['stage' => $this->gloss($line->step)->word]) }}</native:text>
                <native:text>{{ $line->said }}</native:text>
                @if ($line->detail !== '')
                    <x-operator::note>{{ $line->detail }}</x-operator::note>
                @endif
                <x-operator::gloss :gloss="$this->gloss($line->step)" />
            </x-operator::entry>
        @empty
            <x-operator::note>{{ __('health.walkthrough.said_nothing') }}</x-operator::note>
        @endforelse

        @if ($this->answer()->record->linkSaid !== '')
            <native:text>{{ __($this->answer()->record->linkSaid) }}</native:text>
        @endif

        @if ($this->answer()->record->inBackground)
            <native:text>{{ __('health.walkthrough.in_background') }}</native:text>
        @endif

        {{-- Where it hands the operator on to, named, rather than a dead end
             at the moment somebody has just got what they came for. --}}
        @if ($this->answer()->record->next !== [])
            <x-operator::emphasis>{{ __('health.walkthrough.what_next') }}</x-operator::emphasis>
            @forelse ($this->answer()->record->next as $next)
                <native:text>{{ __($next->said) }}</native:text>
                @if ($next->leadsToWhereToWatch)
                    <x-operator::quiet-action label="{{ __('health.walkthrough.where_to_watch') }}" :goes="$this->goes()->whoGetsIn()->clients()" />
                @endif
            @empty
                <x-operator::note>{{ __('health.walkthrough.nothing_next') }}</x-operator::note>
            @endforelse
        @endif

        @if ($this->answer()->record->suggestions !== [])
            <x-operator::emphasis>{{ __('health.walkthrough.suggested') }}</x-operator::emphasis>
            @forelse ($this->answer()->record->suggestions as $suggestion)
                <x-operator::quiet-action label="{{ __('health.walkthrough.walk_this', ['item' => $suggestion]) }}" tap="walkSuggested('{{ $loop->index }}')" />
            @empty
                <x-operator::note>{{ __('health.walkthrough.nothing_suggested') }}</x-operator::note>
            @endforelse
        @endif
    @else
        {{-- Nothing started here yet: the road a walk takes, each step the
             glossary's word for it, explained where the glossary carries it. --}}
        <x-operator::emphasis>{{ __('health.walkthrough.offer') }}</x-operator::emphasis>
        <native:text>{{ __('health.walkthrough.offer_explained') }}</native:text>
        <x-operator::note>{{ __('health.walkthrough.road') }}</x-operator::note>
        @forelse ($this->answer()->road as $step)
            <x-operator::entry>
                <native:text>{{ __('health.at_stage', ['stage' => $this->gloss($step)->word]) }}</native:text>
                <x-operator::gloss :gloss="$this->gloss($step)" />
            </x-operator::entry>
        @empty
            <x-operator::note>{{ __('health.walkthrough.no_road') }}</x-operator::note>
        @endforelse
    @endif

    @unless ($this->answer()->isWorking)
        <native:outlined-text-input
            native:model="looking"
            label="{{ __('health.walkthrough.walk_label') }}"
            placeholder="{{ __('health.walkthrough.walk_placeholder') }}"
        />
        <x-operator::note>{{ __('health.walkthrough.blank_picks') }}</x-operator::note>
        <x-operator::action label="{{ __('health.walkthrough.walk') }}" tap="walk()" />
    @endunless

    @if ($this->answer()->wasStarted)
        <x-operator::action label="{{ __('health.ask_again') }}" tap="again()" />
    @endif
</x-operator::content>
@else
    <x-operator::what-stopped-the-reading
        :went="$this->answer()->went"
        :sign-in-goes-to="$this->goes()->signIn()"
    />
@endif

<x-operator::screen-closes :goes="$this->goes()" here="health" />
