<x-wayfinding::stack-opens :back="$this->hasAWayBack()" :title="$this->stack()->name()->shown()" :stacks="$this->stacksToChooseFrom()" :choosing="$this->choosingAStack" />

@if ($this->answer()->went->cameBack())
<x-operator::content>
    <x-design::title>{{ __('scheduled.title') }}</x-design::title>
    <x-design::body>{{ __('scheduled.between_runs') }}</x-design::body>

    @forelse ($this->answer()->services as $service)
        <x-design::section :label="$service->name">
            <x-design::notice :tone="$service->tone">
                <x-design::strong>{{ __($service->healthSaid) }}</x-design::strong>

                @if ($service->last->ended->said !== '')
                    <x-design::body>{{ __('scheduled.ran', ['ago' => trans_choice($service->last->ended->said, $service->last->ended->count)]) }}</x-design::body>
                    <x-design::body>{{ trans_choice($service->last->tookSaid, $service->last->tookCount) }}</x-design::body>
                @endif

                @if ($service->last->exit->said !== '')
                    <x-design::body>{{ __($service->last->exit->said, ['code' => $service->last->exit->code]) }}</x-design::body>
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

            @if ($service->last->exit->said !== '')
                <x-design::heading>{{ __('scheduled.log') }}</x-design::heading>

                @forelse ($service->last->log as $line)
                    <x-design::verbatim>{{ $line }}</x-design::verbatim>
                @empty
                    <x-design::body>{{ __('scheduled.no_log') }}</x-design::body>
                @endforelse
            @endif
        </x-design::section>
    @empty
        <x-design::row :headline="__('scheduled.nothing')" />
    @endforelse

    <x-design::action :label="__('health.ask_again')" tap="askAgain()" tone="tonal" />
</x-operator::content>
@else
    <x-operator::what-stopped-the-reading
        :settings-would-not-open="$this->theSettingsWouldNotOpen"
        :went="$this->answer()->went"
        :goes="$this->goes()"
    />
@endif

<x-operator::screen-closes :goes="$this->goes()" :here="$this->itsTab()" :marks="$this->marks()" />
