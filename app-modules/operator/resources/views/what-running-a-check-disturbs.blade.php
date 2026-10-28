@use('Modules\Kernel\Api\CheckingWhatDisturbs')
<x-wayfinding::stack-opens :back="$this->hasAWayBack()" :title="$this->stack()->name()->shown()" :stacks="$this->stacksToChooseFrom()" :choosing="$this->choosingAStack" />

@if ($this->answer()->went->cameBack())
<x-operator::content>
    <x-operator::heading>{{ __('health.disturbs.title') }}</x-operator::heading>

    @if ($this->answer()->isFound())
        {{-- The finding as it stands, and after a run, as the run left it. --}}
        <x-design::card>
            <x-design::strong>{{ $this->answer()->title }}</x-design::strong>
            <x-design::strong>{{ __($this->answer()->verdict) }}</x-design::strong>
            @if ($this->answer()->meaning !== '')
                <x-design::body>{{ $this->answer()->meaning }}</x-design::body>
            @endif
            @if ($this->ranAgo()->said !== '')
                <x-design::note>{{ __('health.disturbs.ran', ['ago' => trans_choice($this->ranAgo()->said, $this->ranAgo()->count)]) }}</x-design::note>
            @endif
        </x-design::card>
    @else
        <x-design::notice tone="unknown">
            <x-design::strong>{{ __('health.disturbs.not_found') }}</x-design::strong>
        </x-design::notice>
    @endif

    @if ($this->howItIsGoing()->stands->went->cameBack())
        @if ($this->howItIsGoing()->stands->isWorking())
            <x-design::standing :said="__('health.disturbs.working')" tone="working" />
        @elseif ($this->howItIsGoing()->stands->hasEnded())
            <x-design::notice tone="unknown">
                <x-design::strong>{{ __('health.disturbs.no_outcome') }}</x-design::strong>
            </x-design::notice>
        @elseif ($this->howItIsGoing()->refused !== null)
            <x-design::notice>
                <x-design::strong>{{ __('health.disturbs.refused') }}</x-design::strong>
                <x-operator::refused-in-its-words :refused="$this->howItIsGoing()->refused" />
            </x-design::notice>
        @endif

        @if ($this->answer()->check !== '' && ! $this->howItIsGoing()->stands->isWorking())
            {{-- What it disturbs and for how long, in the stack's words and
                 figures, and the downloads it interrupts, before the yes. --}}
            <x-design::notice>
                <x-design::strong>{{ __('health.disturbs.what') }}</x-design::strong>
                <x-design::body>{{ $this->answer()->disturbs }}</x-design::body>
                <x-design::body>{{ __($this->answer()->boundSaid, ['seconds' => $this->answer()->seconds]) }}</x-design::body>
                @if ($this->downloadsMoving() !== [])
                    <x-design::body>{{ __('health.disturbs.interrupts') }}</x-design::body>
                @endif
                @forelse ($this->downloadsMoving() as $download)
                    <x-design::body>{{ $download }}</x-design::body>
                @empty
                    {{-- Nothing: no download is moving, or the stack could not say. --}}
                @endforelse
            </x-design::notice>
            <x-design::note>{{ __('health.disturbs.not_a_rehearsal') }}</x-design::note>
            <x-operator::offered-action :label="__('health.disturbs.run_it')" tap="run()" :offer="$this->offered(CheckingWhatDisturbs::Run)" />
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
