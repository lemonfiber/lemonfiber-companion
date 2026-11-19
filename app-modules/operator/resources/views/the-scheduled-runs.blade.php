@use('Modules\Kernel\Api\RunningItNow')
<x-wayfinding::stack-opens :back="$this->hasAWayBack()" :title="$this->stack()->name()->shown()" :stacks="$this->stacksToChooseFrom()" :choosing="$this->choosingAStack" />

@if ($this->answer()->went->cameBack())
<x-operator::content>
@if ($this->toRun !== null)
    <x-design::title>{{ __('scheduled.now.about_to', ['service' => $this->runNamed]) }}</x-design::title>
    <x-design::body>{{ __('scheduled.now.whatever_is_due') }}</x-design::body>
    <x-operator::offered-action :label="__('scheduled.now.run')" tap="runItNow()" :offer="$this->offered(RunningItNow::Run)" />
    <x-design::action :label="__('scheduled.now.leave_it')" tap="leaveTheRun()" tone="tonal" />
@else
    <x-design::title>{{ __('scheduled.title') }}</x-design::title>
    <x-design::body>{{ __('scheduled.between_runs') }}</x-design::body>

    @if ($this->howTheRunGoes()->stands->went->cameBack())
        @if ($this->howTheRunGoes()->stands->isWorking())
            <x-design::standing :said="__('scheduled.now.running', ['service' => $this->runNamed])" tone="working" />
        @elseif ($this->howTheRunGoes()->stands->hasEnded())
            <x-operator::why-it-ended :stands="$this->howTheRunGoes()->stands" />
            <x-design::notice tone="unknown">
                <x-design::strong>{{ __('scheduled.now.no_outcome', ['service' => $this->runNamed]) }}</x-design::strong>
            </x-design::notice>
        @elseif ($this->howTheRunGoes()->refused !== null)
            <x-design::notice>
                <x-design::strong>{{ __('scheduled.now.refused', ['service' => $this->runNamed]) }}</x-design::strong>
                <x-operator::refused-in-its-words :refused="$this->howTheRunGoes()->refused" />
            </x-design::notice>
        @elseif ($this->ranNow !== null)
            <x-design::notice :tone="$this->ranNow->tone">
                @if ($this->ranNow->held !== '')
                    <x-design::strong>{{ __('scheduled.now.held', ['service' => $this->ranNow->name]) }}</x-design::strong>
                    <x-operator::emphasis>{{ $this->ranNow->held }}</x-operator::emphasis>
                @else
                    <x-design::strong>{{ __('scheduled.now.ran', ['service' => $this->ranNow->name]) }}</x-design::strong>
                    <x-design::body>{{ __($this->ranNow->healthSaid) }}</x-design::body>
                    <x-design::body>{{ trans_choice($this->ranNow->last->tookSaid, $this->ranNow->last->tookCount) }}</x-design::body>
                    <x-operator::how-a-run-ended :last="$this->ranNow->last" />
                @endif
            </x-design::notice>
        @endif
    @else
        <x-operator::what-stood-in-the-way
            :settings-would-not-open="$this->theSettingsWouldNotOpen"
            :went="$this->howTheRunGoes()->stands->went"
            :goes="$this->goes()"
        />
    @endif

    @forelse ($this->answer()->services as $service)
        <x-design::section :label="$service->name">
            <x-design::notice :tone="$service->tone">
                <x-design::strong>{{ __($service->healthSaid) }}</x-design::strong>

                @if ($service->last->ended->said !== '')
                    <x-design::body>{{ __('scheduled.ran', ['ago' => trans_choice($service->last->ended->said, $service->last->ended->count)]) }}</x-design::body>
                    <x-design::body>{{ trans_choice($service->last->tookSaid, $service->last->tookCount) }}</x-design::body>
                @endif
            </x-design::notice>

            @if ($service->running)
                <x-design::notice tone="working">
                    <x-design::strong>{{ __('scheduled.running') }}</x-design::strong>
                </x-design::notice>
            @endif

            @if (! $service->fired)
                <x-design::notice tone="attention">
                    <x-design::strong>{{ __('scheduled.not_fired') }}</x-design::strong>
                </x-design::notice>
            @endif

            @if ($service->skipped > 0)
                <x-design::body>{{ trans_choice('scheduled.skipped', $service->skipped) }}</x-design::body>
            @endif

            <x-operator::how-a-run-ended :last="$service->last" />

            @unless ($this->howTheRunGoes()->stands->isWorking())
                <x-operator::offered-action :label="__('scheduled.now.run')" :answers-to="__('scheduled.now.run_named', ['service' => $service->name])" :tap="$this->tapping('askToRun', $service->id)" tone="tonal" :offer="$this->offered(RunningItNow::Run)" />
            @endunless
        </x-design::section>
    @empty
        <x-design::row :headline="__('scheduled.nothing')" />
    @endforelse

    <x-design::action :label="__('health.ask_again')" tap="askAgain()" tone="tonal" />
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
