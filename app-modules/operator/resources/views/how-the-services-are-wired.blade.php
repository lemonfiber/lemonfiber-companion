<x-wayfinding::stack-opens :title="$this->stack()->name()->shown()" :stacks="$this->stacksToChooseFrom()" :choosing="$this->choosingAStack" />

@if ($this->answer()->went->cameBack())
<x-operator::content>
    <x-design::title>{{ __('stacks.wiring.road_in') }}</x-design::title>

    {{-- What answers what, before the services and the run: every capability
         a service asks for, which service answers it and how that was settled,
         in the stack's words and order. Read only; a contest is drawn with
         every claimant and nothing picked. --}}
    <x-design::heading>{{ __('stacks.wiring.fills.label') }}</x-design::heading>
    @if ($this->whatAnswersWhat() === null)
        {{-- This frame read the services, so what answers what is read on
             the next one. --}}
        <x-operator::the-next-frame />
    @elseif (! $this->whatAnswersWhat()->went->cameBack())
        <x-design::body>{{ __('stacks.wiring.fills.unreadable') }}</x-design::body>
        <x-operator::what-stood-in-the-way
            :settings-would-not-open="$this->theSettingsWouldNotOpen"
            :went="$this->whatAnswersWhat()->went"
            ask-again=""
            :sign-in-goes-to="$this->goes()->signIn()"
        />
    @elseif ($this->whatAnswersWhat()->refused !== null)
        {{-- A wiring the stack could not read is said through its own words,
             and is never drawn as settled. --}}
        <x-design::notice tone="unknown">
            <x-design::strong>{{ __('stacks.wiring.fills.unreadable') }}</x-design::strong>
            <x-operator::refused-in-its-words :refused="$this->whatAnswersWhat()->refused" />
        </x-design::notice>
    @else
        @forelse ($this->whatAnswersWhat()->links as $link)
            @if ($link->isContested)
                {{-- Nothing answers until somebody chooses, so it is told as a
                     notice, with every candidate in the stack's order. --}}
                <x-design::notice>
                    <x-design::strong>{{ __($link->asks, $link->asksWith) }}</x-design::strong>
                    <x-design::body>{{ __($link->settledSaid) }}</x-design::body>
                    <x-operator::claimed-by :claimants="$link->claimants" />
                </x-design::notice>
            @else
                <x-design::card>
                    <x-design::strong>{{ $link->asks === '' ? $link->by : __($link->asks, $link->asksWith) }}</x-design::strong>
                    <x-design::body>{{ __($link->settledSaid, $link->settledWith) }}</x-design::body>
                    @if ($link->why !== '')
                        <x-design::note>{{ __('stacks.wiring.fills.why', ['why' => $link->why]) }}</x-design::note>
                    @endif
                    <x-operator::claimed-by :claimants="$link->claimants" />
                </x-design::card>
            @endif
        @empty
            <x-design::body>{{ __('stacks.wiring.fills.none') }}</x-design::body>
        @endforelse
    @endif

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
            :settings-would-not-open="$this->theSettingsWouldNotOpen"
            :went="$this->howItIsGoing()->went"
            :sign-in-goes-to="$this->goes()->signIn()"
        />
    @endif
</x-operator::content>
@else
    <x-operator::what-stopped-the-reading
        :settings-would-not-open="$this->theSettingsWouldNotOpen"
        :went="$this->answer()->went"
        :sign-in-goes-to="$this->goes()->signIn()"
    />
@endif

<x-operator::screen-closes :goes="$this->goes()" :here="$this->itsTab()" :marks="$this->marks()" />
