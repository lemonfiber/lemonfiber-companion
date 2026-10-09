@use('Modules\Kernel\Api\WhatToDoWithARun')
@use('Modules\Stacks\Api\AStacksScreen')
<x-wayfinding::stack-opens :title="$this->stack()->name()->shown()" :stacks="$this->stacksToChooseFrom()" :choosing="$this->choosingAStack" />

@if ($this->answer()->went->cameBack())
<x-operator::content>
@if (! $this->answer()->namesARun)
    {{-- Opened on no run at all, so there is nothing to show and nothing to
         agree to. The record is where one is chosen. --}}
    <x-operator::emphasis>{{ __('stacks.run_back.names_no_run') }}</x-operator::emphasis>
    <x-operator::action label="{{ __('stacks.record.road_in') }}" :goes="$this->goes()->to(AStacksScreen::Record)" />
@elseif ($this->wasAgreedTo())
    {{-- What the stack did, drawn from its report and never from the record's
         rows above it. --}}
    @if (! $this->done()->went->cameBack())
        <x-operator::what-stood-in-the-way
            :settings-would-not-open="$this->theSettingsWouldNotOpen"
            :went="$this->done()->went"
            :goes="$this->goes()"
        />
    @elseif ($this->done()->isWorking)
        <x-operator::emphasis>{{ __('stacks.run_back.putting_back') }}</x-operator::emphasis>

        {{-- The stack says what went back once it has finished, and not while
             it runs. --}}
        <x-design::body>{{ __('stacks.run_back.no_progress_while_running') }}</x-design::body>
    @elseif ($this->done()->refused !== null)
        {{-- The stack's answer, in its words, and not a fault: asking after
             the same work is answered the same way, so the one road offered
             is back to the record. --}}
        <x-operator::heading>{{ __('stacks.run_back.refused') }}</x-operator::heading>
        <x-operator::refused-in-its-words :refused="$this->done()->refused" />
        <x-design::body>{{ __('stacks.run_back.refused_same_answer') }}</x-design::body>
        <x-operator::action label="{{ __('stacks.record.road_in') }}" :goes="$this->goes()->to(AStacksScreen::Record)" />
    @elseif ($this->done()->hasEnded)
        {{-- Not a failure: it may well have gone back, and the record is where
             to look. --}}
        <x-operator::emphasis>{{ __('stacks.run_back.no_outcome') }}</x-operator::emphasis>
        <x-design::body>{{ __('stacks.run_back.no_outcome_action') }}</x-design::body>
        <x-operator::action label="{{ __('stacks.record.road_in') }}" :goes="$this->goes()->to(AStacksScreen::Record)" />
    @else
        <x-operator::what-going-back-came-to :report="$this->done()" />

        <x-operator::action label="{{ __('stacks.record.road_in') }}" :goes="$this->goes()->to(AStacksScreen::Record)" />
    @endif

    @if ($this->done()->refused === null)
        <x-operator::quiet-action label="{{ __('health.ask_again') }}" tap="askAgain()" />
    @endif
@elseif (! $this->answer()->isOnTheRecord)
    {{-- The record holds nothing under this stamp: it may have fallen past
         the horizon, or been put back already. Nothing is offered. --}}
    <x-operator::emphasis>{{ __('stacks.run_back.not_on_the_record') }}</x-operator::emphasis>
    <x-operator::action label="{{ __('stacks.record.road_in') }}" :goes="$this->goes()->to(AStacksScreen::Record)" />
    <x-operator::quiet-action label="{{ __('health.ask_again') }}" tap="askAgain()" />
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
            <x-design::body>{{ __($change->reversalSaid) }}</x-design::body>

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
        <x-design::body>{{ __('stacks.run_back.whole_or_nothing') }}</x-design::body>
        <x-operator::offered-action label="{{ __('stacks.run_back.put_it_back') }}" tap="agree()" :offer="$this->offered(WhatToDoWithARun::PutBack)" />
    @else
        {{-- A row says it cannot go back, and the stack judges every change
             before touching any, so it would put none of the run back. --}}
        <x-operator::emphasis>{{ __('stacks.run_back.cannot_go_back') }}</x-operator::emphasis>
    @endif

    <x-operator::quiet-action label="{{ __('health.ask_again') }}" tap="askAgain()" />
@endif
</x-operator::content>
@else
    {{-- The record could not be read, so there is nothing to agree to and
         nothing is offered. --}}
    <x-operator::what-stopped-the-reading
        :settings-would-not-open="$this->theSettingsWouldNotOpen"
        :went="$this->answer()->went"
        :goes="$this->goes()"
    />
@endif

<x-operator::screen-closes :goes="$this->goes()" :here="$this->itsTab()" :marks="$this->marks()" />
