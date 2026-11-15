<x-wayfinding::stack-opens :back="$this->hasAWayBack()" :title="$this->stack()->name()->shown()" :stacks="$this->stacksToChooseFrom()" :choosing="$this->choosingAStack" />

@if ($this->answer()->went->cameBack())
<x-operator::content>
    <x-design::title>{{ __('watcher.title') }}</x-design::title>

    @if ($this->answer()->blindBecause !== '')
        <x-design::notice tone="unknown">
            <x-design::strong>{{ __('watcher.blind') }}</x-design::strong>
            {{-- The stack's own words for why the watcher does not answer. --}}
            <x-design::body>{{ $this->answer()->blindBecause }}</x-design::body>
        </x-design::notice>
    @else
        <x-design::body>{{ __('watcher.watched_by', ['watcher' => $this->answer()->watcher, 'ago' => trans_choice($this->answer()->read->said, $this->answer()->read->count)]) }}</x-design::body>
    @endif

    @if ($this->servicesAreBeingRead())
        {{-- This frame read the watcher, so the stack's own reading is read on the next one. --}}
        <x-design::the-next-frame />
    @endif

    <x-design::section :label="__('watcher.monitors')">
        @forelse ($this->answer()->monitors as $monitor)
            <x-design::row :headline="$monitor->name" :supporting="__($monitor->kindSaid)" :trailing="__($monitor->stateSaid)" />

            <x-design::notice :tone="$monitor->tone">
                <x-design::body>{{ __('watcher.said_by_the_watcher', ['state' => __($monitor->stateSaid), 'ago' => trans_choice($monitor->since->said, $monitor->since->count)]) }}</x-design::body>

                @if ($monitor->stackSaid !== '')
                    <x-design::body>{{ __('watcher.said_by_the_stack', ['state' => __($monitor->stackSaid)]) }}</x-design::body>
                @endif

                @if ($monitor->disagree)
                    <x-design::strong>{{ __('watcher.they_disagree') }}</x-design::strong>
                @endif

                @if ($monitor->covers->name !== '')
                    <x-design::body>{{ __('watcher.covers_this', ['kind' => __($monitor->covers->said), 'name' => $monitor->covers->name]) }}</x-design::body>
                @endif

                @if ($monitor->pathSaid !== '')
                    <x-design::body>{{ __($monitor->pathSaid) }}</x-design::body>
                @endif

                @if ($monitor->neverCame)
                    <x-design::body>{{ __('watcher.heartbeat_never') }}</x-design::body>
                @elseif ($monitor->missing->said !== '')
                    <x-design::body>{{ __('watcher.heartbeat_missing', ['ago' => trans_choice($monitor->missing->said, $monitor->missing->count)]) }}</x-design::body>
                @endif

                @if ($monitor->wanted !== '')
                    {{-- The stack's own words for what the monitor was meant to watch, what it watches instead and why. --}}
                    <x-design::body>{{ __('watcher.not_set_up', ['wanted' => $monitor->wanted, 'watches' => $monitor->watches]) }}</x-design::body>
                    <x-design::body>{{ $monitor->why }}</x-design::body>
                @endif

                @if ($monitor->switchedOff)
                    <x-design::body>{{ __('watcher.switched_off') }}</x-design::body>
                @endif
            </x-design::notice>
        @empty
            <x-design::row :headline="__('watcher.no_monitors')" />
        @endforelse
    </x-design::section>

    <x-design::section :label="__('watcher.uncovered')">
        @forelse ($this->answer()->uncovered as $thing)
            <x-design::row :headline="$thing->name" :supporting="__($thing->said)" />
        @empty
            <x-design::row :headline="__('watcher.nothing_uncovered')" />
        @endforelse
    </x-design::section>

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
