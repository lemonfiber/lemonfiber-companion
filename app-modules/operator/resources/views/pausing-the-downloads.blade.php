<x-wayfinding::stack-opens :back="$this->hasAWayBack()" :title="$this->stack()->name()->shown()" :stacks="$this->stacksToChooseFrom()" :choosing="$this->choosingAStack" />

@if ($this->answer()->stands->went->cameBack())
<x-operator::content>
@if (! $this->answer()->namesWhat)
    {{-- Opened on neither pausing nor resuming, so there is nothing to
         rehearse and nothing to agree to. --}}
    <x-operator::emphasis>{{ __('stacks.pausing.names_nothing') }}</x-operator::emphasis>
@elseif ($this->wasAgreedTo())
    {{-- What each client read back, drawn from the stack's report and never
         from the rehearsal, so a rehearsal cannot read as something that
         happened. --}}
    @if (! $this->done()->stands->went->cameBack())
        <x-operator::what-stood-in-the-way
            :settings-would-not-open="$this->theSettingsWouldNotOpen"
            :went="$this->done()->stands->went"
            :goes="$this->goes()"
        />
    @elseif ($this->done()->stands->isWorking())
        <x-operator::emphasis>{{ __($this->says()->working) }}</x-operator::emphasis>
    @elseif ($this->done()->stands->hasEnded())
        <x-operator::emphasis>{{ __('stacks.pausing.no_outcome') }}</x-operator::emphasis>
        <x-design::body>{{ __('stacks.pausing.no_outcome_action') }}</x-design::body>
    @elseif ($this->done()->said !== null)
        @if ($this->done()->wasRehearsed)
            <x-operator::heading>{{ __('stacks.pausing.a_rehearsal') }}</x-operator::heading>
        @else
            <x-operator::heading>{{ __($this->says()->done) }}</x-operator::heading>
        @endif

        @forelse ($this->done()->said->clients as $client)
            <x-operator::entry>
                <x-design::body>{{ $client->name }}</x-design::body>
                @if ($client->reached)
                    <x-operator::note>{{ __('stacks.pausing.before_and_after', ['was' => __($client->wasSaid), 'now' => $client->nowSaid === '' ? __('stacks.pausing.said.unsaid') : __($client->nowSaid)]) }}</x-operator::note>
                @else
                    <x-operator::emphasis>{{ __('stacks.pausing.unreached') }}</x-operator::emphasis>
                    <x-operator::note>{{ $client->why === '' ? __('stacks.pausing.unreached_no_reason') : $client->why }}</x-operator::note>
                @endif
            </x-operator::entry>
        @empty
            <x-design::body>{{ __('stacks.pausing.no_clients') }}</x-design::body>
        @endforelse

        @if ($this->done()->said->caution !== '')
            <x-operator::emphasis>{{ $this->done()->said->caution }}</x-operator::emphasis>
        @endif
    @endif

    <x-operator::quiet-action :label="__('health.ask_again')" tap="askAgain()" />
@elseif ($this->answer()->stands->isWorking())
    {{-- The stack answers the rehearsal as work, so it may not be in yet.
         Said as that, and read again on the screen's cadence. --}}
    <x-operator::emphasis>{{ __('stacks.pausing.working_it_out') }}</x-operator::emphasis>
    <x-operator::quiet-action :label="__('health.ask_again')" tap="askAgain()" />
@elseif ($this->answer()->stands->hasEnded())
    <x-operator::emphasis>{{ __('stacks.pausing.offer_ended') }}</x-operator::emphasis>
    <x-operator::quiet-action :label="__('health.ask_again')" tap="askAgain()" />
@elseif ($this->answer()->said !== null)
    {{-- The rehearsal: what every client is doing now, all of it before the
         yes. Nothing below has happened, and nothing is worded as though it
         had. --}}
    <x-operator::heading>{{ __($this->says()->whatItDoes) }}</x-operator::heading>
    <x-design::body>{{ __($this->says()->explained) }}</x-design::body>

    @forelse ($this->answer()->said->clients as $client)
        <x-operator::entry>
            <x-design::body>{{ $client->name }}</x-design::body>
            @if ($client->reached)
                <x-operator::note>{{ __($client->wasSaid) }}</x-operator::note>
            @else
                <x-operator::emphasis>{{ __('stacks.pausing.unreached') }}</x-operator::emphasis>
                <x-operator::note>{{ $client->why === '' ? __('stacks.pausing.unreached_no_reason') : $client->why }}</x-operator::note>
            @endif
        </x-operator::entry>
    @empty
        <x-design::body>{{ __('stacks.pausing.no_clients') }}</x-design::body>
    @endforelse

    @if ($this->answer()->said->caution !== '')
        <x-operator::emphasis>{{ $this->answer()->said->caution }}</x-operator::emphasis>
    @endif

    @if ($this->answer()->said->clients !== [])
        <x-operator::offered-action :label="__($this->says()->agree)" tap="agree()" :offer="$this->offered($this->whatWasAsked())" />
    @endif
    <x-operator::quiet-action :label="__('health.ask_again')" tap="askAgain()" />
@endif
</x-operator::content>
@else
    {{-- The stack would not rehearse, so there is nothing to agree to and
         nothing is offered. --}}
    <x-operator::what-stopped-the-reading
        :settings-would-not-open="$this->theSettingsWouldNotOpen"
        :went="$this->answer()->stands->went"
        :goes="$this->goes()"
    />
@endif

<x-operator::screen-closes :goes="$this->goes()" :here="$this->itsTab()" :marks="$this->marks()" />
