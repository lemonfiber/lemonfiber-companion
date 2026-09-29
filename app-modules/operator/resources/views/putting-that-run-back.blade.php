<x-operator::screen-opens :title="$this->stack()->name()->shown()" />

@if ($this->answer()->went->cameBack())
<x-operator::content>
@if (! $this->answer()->namesARun)
    {{-- Opened on no run at all, so there is nothing to show and nothing to
         agree to. The record is where one is chosen. --}}
    <x-operator::emphasis>{{ __('stacks.run_back.names_no_run') }}</x-operator::emphasis>
    <x-operator::action label="{{ __('stacks.record.road_in') }}" :goes="$this->goes()->ofItself()->record()" />
@elseif ($this->wasAgreedTo())
    {{-- What the stack did, drawn from its report and never from the record's
         rows above it. --}}
    @if (! $this->done()->went->cameBack())
        <x-operator::what-stood-in-the-way
            :went="$this->done()->went"
            :sign-in-goes-to="$this->goes()->signIn()"
        />
    @elseif ($this->done()->isWorking)
        <x-operator::emphasis>{{ __('stacks.run_back.putting_back') }}</x-operator::emphasis>

        {{-- The stack says what went back once it has finished, and not while
             it runs. --}}
        <native:text>{{ __('stacks.run_back.no_progress_while_running') }}</native:text>
        <x-operator::note>
            {{ __($this->cadence()->saidOnTheScreen(), ['count' => $this->cadence()->seconds()]) }}
        </x-operator::note>
    @elseif ($this->done()->refused !== null)
        {{-- The stack's answer, in its words, and not a fault: asking after
             the same work is answered the same way, so the one road offered
             is back to the record. --}}
        <x-operator::heading>{{ __('stacks.run_back.refused') }}</x-operator::heading>
        <x-operator::refused-in-its-words :refused="$this->done()->refused" />
        <native:text>{{ __('stacks.run_back.refused_same_answer') }}</native:text>
        <x-operator::action label="{{ __('stacks.record.road_in') }}" :goes="$this->goes()->ofItself()->record()" />
    @elseif ($this->done()->hasEnded)
        {{-- Not a failure: it may well have gone back, and the record is where
             to look. --}}
        <x-operator::emphasis>{{ __('stacks.run_back.no_outcome') }}</x-operator::emphasis>
        <native:text>{{ __('stacks.run_back.no_outcome_action') }}</native:text>
        <x-operator::action label="{{ __('stacks.record.road_in') }}" :goes="$this->goes()->ofItself()->record()" />
    @else
        @if ($this->done()->rehearsed)
            {{-- A rehearsal, and said to be one before anything else: nothing
                 below has happened, and nothing is worded as though it had. --}}
            <x-operator::heading>{{ __('stacks.run_back.a_rehearsal') }}</x-operator::heading>
        @endif

        {{-- What was left leads, because it is the part worth reading: an
             operator who believes a run went back and finds half of it still
             standing has been told something false. --}}
        <x-operator::emphasis>{{ __($this->done()->headline) }}</x-operator::emphasis>

        @forelse ($this->done()->left as $left)
            <x-operator::entry>
                <x-operator::emphasis>{{ $left->target }}</x-operator::emphasis>
                <native:text>{{ $left->because }}</native:text>
            </x-operator::entry>
        @empty
            {{-- Nothing was left, which the line above says. --}}
        @endforelse

        @forelse ($this->done()->noted as $noted)
            @if ($loop->first)
                {{-- It went back and still leaves something behind: a setting
                     that re-pointed where data lives goes back while the data
                     stays where it was moved. --}}
                <x-operator::emphasis>{{ __('stacks.run_back.noted') }}</x-operator::emphasis>
            @endif

            <x-operator::entry>
                <x-operator::emphasis>{{ $noted->target }}</x-operator::emphasis>
                <native:text>{{ $noted->because }}</native:text>
            </x-operator::entry>
        @empty
            {{-- Nothing about going back needed saying. --}}
        @endforelse

        <x-operator::emphasis>{{ __($this->done()->reversedSaid) }}</x-operator::emphasis>

        @forelse ($this->done()->reversed as $reversed)
            <x-operator::entry>
                <native:text>{{ $reversed->target }}</native:text>
                <x-operator::note>{{ __($reversed->doesSaid) }}</x-operator::note>
            </x-operator::entry>
        @empty
            <x-operator::note>{{ __($this->done()->noneReversed) }}</x-operator::note>
        @endforelse

        <x-operator::action label="{{ __('stacks.record.road_in') }}" :goes="$this->goes()->ofItself()->record()" />
    @endif

    @if ($this->done()->refused === null)
        <x-operator::quiet-action label="{{ __('health.ask_again') }}" tap="again()" />
    @endif
@elseif (! $this->answer()->isOnTheRecord)
    {{-- The record holds nothing under this stamp: it may have fallen past
         the horizon, or been put back already. Nothing is offered. --}}
    <x-operator::emphasis>{{ __('stacks.run_back.not_on_the_record') }}</x-operator::emphasis>
    <x-operator::action label="{{ __('stacks.record.road_in') }}" :goes="$this->goes()->ofItself()->record()" />
    <x-operator::quiet-action label="{{ __('health.ask_again') }}" tap="again()" />
@else
    {{-- The agreement, drawn from the record's own rows. The stack puts the
         whole run back or none of it, so what goes with it is said before the
         yes, with the count the record gives. --}}
    <x-operator::emphasis>{{ trans_choice('stacks.run_back.goes_with_it', $this->answer()->alongside) }}</x-operator::emphasis>
    <x-operator::note>{{ trans_choice($this->answer()->whenSaid, $this->answer()->whenCount) }}</x-operator::note>

    @forelse ($this->answer()->changes as $change)
        <x-operator::entry>
            <x-operator::emphasis>{{ $change->did }}</x-operator::emphasis>
            <x-operator::note>{{ __('stacks.record.by', ['operation' => $change->operation, 'target' => $change->target]) }}</x-operator::note>
            <native:text>{{ __($change->reversalSaid) }}</native:text>

            @if ($change->because !== '')
                <x-operator::note>{{ __('stacks.record.stops_short', ['because' => $change->because]) }}</x-operator::note>
            @endif

            @if ($change->instead !== '')
                <x-operator::note>{{ __('stacks.record.instead', ['instead' => $change->instead]) }}</x-operator::note>
            @endif
        </x-operator::entry>
    @empty
        {{-- Unreachable while this branch is drawn only for a run the record
             holds, and written anyway so a run that lost its rows says so. --}}
        <x-operator::note>{{ __('stacks.run_back.not_on_the_record') }}</x-operator::note>
    @endforelse

    @if ($this->answer()->goesBack)
        <native:text>{{ __('stacks.run_back.whole_or_nothing') }}</native:text>
        <x-operator::action label="{{ __('stacks.run_back.put_it_back') }}" tap="agree()" />
    @else
        {{-- A row says it cannot go back, and the stack judges every change
             before touching any, so it would put none of the run back. --}}
        <x-operator::emphasis>{{ __('stacks.run_back.cannot_go_back') }}</x-operator::emphasis>
    @endif

    <x-operator::quiet-action label="{{ __('health.ask_again') }}" tap="again()" />
@endif
</x-operator::content>
@else
    {{-- The record could not be read, so there is nothing to agree to and
         nothing is offered. --}}
    <x-operator::what-stopped-the-reading
        :went="$this->answer()->went"
        :sign-in-goes-to="$this->goes()->signIn()"
    />
@endif

<x-operator::screen-closes :goes="$this->goes()" here="health" />
