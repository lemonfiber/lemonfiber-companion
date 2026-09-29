<x-operator::screen-opens :title="$this->stack()->name()->shown()" />

@if ($this->answer()->went->cameBack())
<x-operator::content>
    <x-operator::heading>{{ __('stacks.wiring.road_in') }}</x-operator::heading>

    {{-- What a run wires to each other, and how each is running: a service
         that is not up leaves its connections for a later run. --}}
    <x-operator::emphasis>{{ __('stacks.wiring.services') }}</x-operator::emphasis>
    @forelse ($this->answer()->services as $service)
        <x-operator::entry>
            <native:text>{{ $service->name }}</native:text>
            <x-operator::note>{{ __($service->runsSaid) }}</x-operator::note>
        </x-operator::entry>
    @empty
        <x-operator::note>{{ __('stacks.wiring.no_services') }}</x-operator::note>
    @endforelse

    @if ($this->howItIsGoing()->went->cameBack())
        @if ($this->howItIsGoing()->isWorking)
            <native:text>{{ __('stacks.wiring.working') }}</native:text>
            <x-operator::note>
                {{ __($this->cadence()->saidOnTheScreen(), ['count' => $this->cadence()->seconds()]) }}
            </x-operator::note>
        @elseif ($this->howItIsGoing()->hasEnded)
            {{-- Not a failure and not a refusal: the stack has no outcome for it
                 any more. --}}
            <native:text>{{ __('stacks.wiring.no_outcome') }}</native:text>
        @elseif ($this->howItIsGoing()->refusal !== '')
            {{-- The stack's answer, drawn as the reason it is rather than as
                 something to try again. --}}
            <x-operator::emphasis>{{ __('stacks.wiring.refused') }}</x-operator::emphasis>
            <native:text>{{ $this->howItIsGoing()->refusal }}</native:text>
        @elseif ($this->howItIsGoing()->wiring !== null)
            {{-- A run that only said what it would do says so before anything
                 else, and is never drawn as one that wrote. --}}
            @if ($this->howItIsGoing()->wiring->rehearsed)
                <x-operator::emphasis>{{ __('stacks.wiring.rehearsed') }}</x-operator::emphasis>
            @endif

            {{-- Whether drift could be judged, before the connections it is
                 about: a run that could not judge it is its own answer. --}}
            <native:text>{{ __($this->howItIsGoing()->wiring->judgedSaid) }}</native:text>

            @forelse ($this->howItIsGoing()->wiring->connections as $connection)
                <x-operator::entry>
                    <x-operator::emphasis>{{ $connection->connection }}</x-operator::emphasis>
                    <native:text>{{ __($connection->stateSaid) }}</native:text>
                    @if ($connection->said !== '')
                        {{-- The stack's words, or the service's own, as they came. --}}
                        <native:text>{{ $connection->said }}</native:text>
                    @endif
                    @if ($connection->yours !== '')
                        <x-operator::note>{{ __('stacks.wiring.yours', ['value' => $connection->yours]) }}</x-operator::note>
                    @endif
                    @if ($connection->ours !== '')
                        <x-operator::note>{{ __('stacks.wiring.ours', ['value' => $connection->ours]) }}</x-operator::note>
                    @endif
                    @if ($connection->breakage !== '')
                        {{-- A warning says what breaks and what puts it right. --}}
                        <x-operator::emphasis>{{ __('stacks.wiring.breaks', ['breakage' => $connection->breakage]) }}</x-operator::emphasis>
                        <native:text>{{ __('stacks.wiring.remedy', ['remediation' => $connection->remediation]) }}</native:text>
                    @endif
                </x-operator::entry>
            @empty
                <x-operator::note>{{ __('stacks.wiring.no_connections') }}</x-operator::note>
            @endforelse

            <x-operator::heading>{{ __('stacks.wiring.cannot_wire') }}</x-operator::heading>
            @forelse ($this->howItIsGoing()->wiring->unsupported as $line)
                <native:text>{{ __($line->said, $line->with) }}</native:text>
            @empty
                <x-operator::note>{{ __('stacks.wiring.nothing_unsupported') }}</x-operator::note>
            @endforelse
        @endif

        @if (! $this->howItIsGoing()->isWorking)
            {{-- A run changes nothing already right and keeps what was changed
                 by hand, so it is offered as it is. --}}
            <x-operator::note>{{ __('stacks.wiring.what_a_run_does') }}</x-operator::note>
            <x-operator::action label="{{ __('stacks.wiring.wire') }}" tap="wire()" />
        @endif

        {{-- Asks for the services again, and after a run still being followed;
             what the stack said about a run stays. --}}
        <x-operator::quiet-action label="{{ __('health.ask_again') }}" tap="again()" />
    @else
        {{-- Starting a run, or asking after one, met something in the way. --}}
        <x-operator::what-stood-in-the-way
            :went="$this->howItIsGoing()->went"
            :sign-in-goes-to="$this->goes()->signIn()"
        />
    @endif
</x-operator::content>
@else
    <x-operator::what-stopped-the-reading
        :went="$this->answer()->went"
        :sign-in-goes-to="$this->goes()->signIn()"
    />
@endif

<x-operator::screen-closes :goes="$this->goes()" here="health" />
