@use('Modules\Kernel\Api\AcceptingAChoice')
<x-wayfinding::stack-opens :back="$this->hasAWayBack()" :title="$this->stack()->name()->shown()" :stacks="$this->stacksToChooseFrom()" :choosing="$this->choosingAStack" />

@if ($this->answer()->went->cameBack())
<x-operator::content>
    <x-operator::heading>{{ __('health.choice.title') }}</x-operator::heading>

    @if ($this->answer()->raised !== null)
        <x-design::card>
            <x-design::strong>{{ $this->answer()->raised->title }}</x-design::strong>
            <x-design::note>{{ __('health.choice.costs') }}</x-design::note>
            <x-design::body>{{ $this->answer()->raised->meaning }}</x-design::body>
            @forelse ($this->answer()->raised->remedies as $remedy)
                <x-design::body>{{ $remedy->action() }}</x-design::body>
            @empty
                {{-- Nothing: the stack named nothing to do instead. --}}
            @endforelse
        </x-design::card>

        @if ($this->answer()->raised->check === null)
            <x-design::notice tone="unknown">
                <x-design::strong>{{ __('health.choice.accepted') }}</x-design::strong>
                <x-design::body>{{ __('health.choice.accepted_stays') }}</x-design::body>
            </x-design::notice>
        @endif
    @else
        <x-design::notice tone="unknown">
            <x-design::strong>{{ __('health.choice.not_raised') }}</x-design::strong>
        </x-design::notice>
    @endif

    @if ($this->howItIsGoing()->stands->went->cameBack())
        @if ($this->howItIsGoing()->stands->isWorking())
            <x-design::standing :said="__('health.choice.working')" tone="working" />
        @elseif ($this->howItIsGoing()->stands->hasEnded())
            <x-design::notice tone="unknown">
                <x-design::strong>{{ __('health.choice.no_outcome') }}</x-design::strong>
            </x-design::notice>
        @elseif ($this->howItIsGoing()->refused !== null)
            <x-design::notice>
                <x-design::strong>{{ __('health.choice.refused') }}</x-design::strong>
                <x-operator::refused-in-its-words :refused="$this->howItIsGoing()->refused" />
            </x-design::notice>
        @elseif ($this->howItIsGoing()->accepted === false)
            <x-design::notice tone="unknown">
                <x-design::strong>{{ __('health.choice.not_taken') }}</x-design::strong>
            </x-design::notice>
        @endif

        @if ($this->answer()->raised?->check !== null && ! $this->howItIsGoing()->stands->isWorking())
            <x-design::body>{{ __('health.choice.what_accepting_does') }}</x-design::body>
            <x-operator::offered-action :label="__('health.choice.accept_it')" tap="accept()" :offer="$this->offered(AcceptingAChoice::Accept)" />
        @endif

        <x-operator::quiet-action :label="__('health.ask_again')" tap="askAgain()" />
    @else
        <x-operator::what-stood-in-the-way
            :settings-would-not-open="$this->theSettingsWouldNotOpen"
            :went="$this->howItIsGoing()->stands->went"
            :goes="$this->goes()"
        />
    @endif
</x-operator::content>
@else
    <x-operator::what-stopped-the-reading
        :settings-would-not-open="$this->theSettingsWouldNotOpen"
        :went="$this->answer()->went"
        :goes="$this->goes()"
    />
@endif

<x-operator::screen-closes :goes="$this->goes()" :here="$this->itsTab()" :marks="$this->marks()" />
