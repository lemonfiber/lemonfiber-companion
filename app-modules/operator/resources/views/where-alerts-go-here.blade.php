<x-wayfinding::stack-opens :back="$this->hasAWayBack()" :title="$this->stack()->name()->shown()" :stacks="$this->stacksToChooseFrom()" :choosing="$this->choosingAStack" />

@if ($this->answer()->went->cameBack())
<x-operator::content>
    <x-design::title>{{ __('alerts.title') }}</x-design::title>

    @if ($this->answer()->notSetUp)
        <x-design::standing :said="__('alerts.state.unconfigured')" tone="quiet" :note="__('alerts.set_up_at_the_machine')" />
    @else
        <x-design::standing :said="__($this->answer()->stateSaid)" :tone="$this->answer()->stateTone" />

        @forelse ($this->answer()->backEnds as $backEnd)
            <x-operator::an-alert-back-end :back-end="$backEnd" />
        @empty
            <x-design::body>{{ __('alerts.none_readable') }}</x-design::body>
        @endforelse

        @if ($this->answer()->unread !== [])
            <x-design::section :label="__('alerts.unread')">
                @forelse ($this->answer()->unread as $id => $because)
                    <x-design::row :headline="$id" :supporting="$because" />
                @empty
                    <x-design::row :headline="__('alerts.all_read')" />
                @endforelse
            </x-design::section>
        @endif
    @endif

    <x-design::note>{{ __('alerts.changed_at_the_machine') }}</x-design::note>
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
