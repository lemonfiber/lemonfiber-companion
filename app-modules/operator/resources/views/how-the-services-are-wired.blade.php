<x-operator::screen-opens :title="$this->stack()->name()->shown()" />

@if ($this->answer()->went->cameBack())
<x-operator::content>
    <x-design::title>{{ __('stacks.wiring.road_in') }}</x-design::title>

    {{-- What a run wires to each other, and how each is running: a service
         that is not up leaves its connections for a later run. --}}
    <x-design::section :label="__('stacks.wiring.services')">
        @forelse ($this->answer()->services as $service)
            <x-design::row :headline="$service->name" :supporting="__($service->runsSaid)" :tone="$service->tone" />
        @empty
            <x-design::row :headline="__('stacks.wiring.no_services')" />
        @endforelse
    </x-design::section>

    @if ($this->howItIsGoing()->went->cameBack())
        @if ($this->howItIsGoing()->isWorking)
            <x-design::standing
                :said="__('stacks.wiring.working')"
                tone="working"
            />
        @elseif ($this->howItIsGoing()->hasEnded)
            {{-- Not a failure and not a refusal: the stack has no outcome for it
                 any more. --}}
            <x-design::notice tone="unknown">
                <x-design::strong>{{ __('stacks.wiring.no_outcome') }}</x-design::strong>
            </x-design::notice>
        @elseif ($this->howItIsGoing()->refusal !== '')
            {{-- The stack's answer, drawn as the reason it is rather than as
                 something to try again. --}}
            <x-design::notice>
                <x-design::strong>{{ __('stacks.wiring.refused') }}</x-design::strong>
                <x-design::body>{{ $this->howItIsGoing()->refusal }}</x-design::body>
            </x-design::notice>
        @elseif ($this->howItIsGoing()->wiring !== null)
            {{-- A run that only said what it would do says so before anything
                 else, and is never drawn as one that wrote. --}}
            @if ($this->howItIsGoing()->wiring->rehearsed)
                <x-design::notice>
                    <x-design::strong>{{ __('stacks.wiring.rehearsed') }}</x-design::strong>
                </x-design::notice>
            @endif

            {{-- Whether drift could be judged, before the connections it is
                 about: a run that could not judge it is its own answer. --}}
            <x-design::body>{{ __($this->howItIsGoing()->wiring->judgedSaid) }}</x-design::body>

            @forelse ($this->howItIsGoing()->wiring->connections as $connection)
                <x-design::card>
                    <x-design::strong>{{ $connection->connection }}</x-design::strong>
                    <x-design::body>{{ __($connection->stateSaid) }}</x-design::body>
                    @if ($connection->said !== '')
                        {{-- The stack's words, or the service's own, as they came. --}}
                        <x-design::body>{{ $connection->said }}</x-design::body>
                    @endif
                    @if ($connection->yours !== '')
                        <x-design::note>{{ __('stacks.wiring.yours', ['value' => $connection->yours]) }}</x-design::note>
                    @endif
                    @if ($connection->ours !== '')
                        <x-design::note>{{ __('stacks.wiring.ours', ['value' => $connection->ours]) }}</x-design::note>
                    @endif
                    @if ($connection->breakage !== '')
                        {{-- A warning says what breaks and what puts it right. --}}
                        <x-design::strong>{{ __('stacks.wiring.breaks', ['breakage' => $connection->breakage]) }}</x-design::strong>
                        <x-design::body>{{ __('stacks.wiring.remedy', ['remediation' => $connection->remediation]) }}</x-design::body>
                    @endif
                </x-design::card>
            @empty
                <x-design::body>{{ __('stacks.wiring.no_connections') }}</x-design::body>
            @endforelse

            <x-design::section :label="__('stacks.wiring.cannot_wire')">
                @forelse ($this->howItIsGoing()->wiring->unsupported as $line)
                    <x-design::row :headline="__($line->said, $line->with)" />
                @empty
                    <x-design::row :headline="__('stacks.wiring.nothing_unsupported')" />
                @endforelse
            </x-design::section>
        @endif

        @if (! $this->howItIsGoing()->isWorking)
            {{-- A run changes nothing already right and keeps what was changed
                 by hand, so it is offered as it is: the screen's one way
                 forward. --}}
            <x-design::note>{{ __('stacks.wiring.what_a_run_does') }}</x-design::note>
            <x-design::action label="{{ __('stacks.wiring.wire') }}" tap="wire()" />
        @endif

        {{-- Asks for the services again, and after a run still being followed;
             what the stack said about a run stays. --}}
        <x-design::action label="{{ __('health.ask_again') }}" tap="again()" tone="tonal" />
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

<x-operator::screen-closes :goes="$this->goes()" :here="$this->itsTab()" />
