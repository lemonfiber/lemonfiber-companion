@use('Modules\Kernel\Api\CheckingWhatDisturbs')
<x-wayfinding::stack-opens :back="$this->hasAWayBack()" :title="$this->stack()->name()->shown()" :stacks="$this->stacksToChooseFrom()" :choosing="$this->choosingAStack" />

@if ($this->answer()->went->cameBack())
<x-operator::content>
    @if ($this->answer()->familySaid !== '')
        <x-operator::heading>{{ __($this->answer()->titleSaid, ['family' => __($this->answer()->familySaid)]) }}</x-operator::heading>
    @else
        <x-operator::heading>{{ __($this->answer()->titleSaid) }}</x-operator::heading>
    @endif

    @if ($this->howItIsGoing()->went->cameBack())
        @if ($this->howItIsGoing()->isWorking)
            <x-design::standing :said="__('health.run_every.working')" tone="working" />
        @elseif ($this->howItIsGoing()->hasEnded)
            <x-design::notice tone="unknown">
                <x-design::strong>{{ __('health.run_every.no_outcome') }}</x-design::strong>
            </x-design::notice>
        @elseif ($this->howItIsGoing()->refused !== null)
            <x-design::notice>
                <x-design::strong>{{ __('health.run_every.refused') }}</x-design::strong>
                <x-operator::refused-in-its-words :refused="$this->howItIsGoing()->refused" />
            </x-design::notice>
        @endif

        @if ($this->howItIsGoing()->answered)
            {{-- What the run came back with, here: a reading taken afterwards
                 would leave every check that disturbs unverified again. --}}
            <x-design::strong>{{ __('health.run_every.found') }}</x-design::strong>
            @if ($this->ranAgo()->said !== '')
                <x-design::note>{{ __('health.disturbs.ran', ['ago' => trans_choice($this->ranAgo()->said, $this->ranAgo()->count)]) }}</x-design::note>
            @endif
            @forelse ($this->answer()->found as $finding)
                <x-design::card>
                    <x-operator::heading>{{ __($finding->about) }}</x-operator::heading>
                    <x-design::strong>{{ $finding->title }}</x-design::strong>
                    <x-design::strong>{{ __($finding->verdict) }}</x-design::strong>
                    @if ($finding->meaning !== '')
                        <x-design::body>{{ $finding->meaning }}</x-design::body>
                    @endif
                </x-design::card>
            @empty
                <x-design::body>{{ __('health.run_every.found_nothing') }}</x-design::body>
            @endforelse
        @endif

        @if ($this->answer()->runnable && ! $this->howItIsGoing()->isWorking)
            {{-- Every check the run covers that disturbs something, each in the
                 stack's words with its bound, and the downloads it interrupts,
                 before the yes. --}}
            <x-design::notice>
                <x-design::strong>{{ __('health.run_every.what') }}</x-design::strong>
                @forelse ($this->answer()->disturbing as $disturbing)
                    <x-design::body>{{ $disturbing->title }}</x-design::body>
                    <x-design::note>{{ $disturbing->disturbs }}</x-design::note>
                    <x-design::note>{{ __($disturbing->boundSaid, ['seconds' => $disturbing->seconds]) }}</x-design::note>
                @empty
                    <x-design::body>{{ __('health.run_every.disturbs_nothing') }}</x-design::body>
                @endforelse
                @if ($this->downloadsMoving() !== [])
                    <x-design::body>{{ __('health.disturbs.interrupts') }}</x-design::body>
                @endif
                @forelse ($this->downloadsMoving() as $download)
                    <x-design::body>{{ $download }}</x-design::body>
                @empty
                    {{-- Nothing: no download is moving, or the stack could not say. --}}
                @endforelse
            </x-design::notice>
            <x-design::note>{{ __('health.run_every.not_a_rehearsal') }}</x-design::note>
            <x-operator::offered-action :label="__('health.run_every.run_it')" tap="run()" :offer="$this->offered(CheckingWhatDisturbs::Run)" />
        @endif

        <x-operator::quiet-action :label="__('health.ask_again')" tap="askAgain()" />
    @else
        <x-operator::what-stood-in-the-way
            :settings-would-not-open="$this->theSettingsWouldNotOpen"
            :went="$this->howItIsGoing()->went"
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
