<x-wayfinding::stack-opens :back="$this->hasAWayBack()" :title="$this->stack()->name()->shown()" :stacks="$this->stacksToChooseFrom()" :choosing="$this->choosingAStack" />

@if ($this->answer()->went->cameBack())
<x-operator::content>
    @forelse ($this->answer()->backEnds as $backEnd)
        <x-operator::an-alert-back-end :back-end="$backEnd" :opens="false" />

        <x-design::section :label="__('alerts.failures')">
            @forelse ($backEnd->failures as $failure)
                <x-design::row
                    :headline="__($failure->ended->said, ['ago' => trans_choice($failure->ended->ago->said, $failure->ended->ago->count), 'reason' => $failure->ended->filling])"
                    :supporting="__($failure->kindSaid)"
                />
            @empty
                <x-design::row :headline="__('alerts.no_failures')" />
            @endforelse
        </x-design::section>
    @empty
        <x-design::standing :said="__('alerts.no_longer_listed')" tone="unknown" />
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
