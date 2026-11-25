@use('Modules\Operator\Internal\ASettingChangeFollowed')
@use('Modules\Kernel\Api\ChangingASetting')
<x-wayfinding::stack-opens :back="$this->hasAWayBack()" :title="$this->stack()->name()->shown()" :stacks="$this->stacksToChooseFrom()" :choosing="$this->choosingAStack" />

@if ($this->answer()->went->cameBack())
<x-operator::content>
    <x-operator::heading>{{ __('stacks.copy.how_they_run') }}</x-operator::heading>
    <x-design::body>{{ __('stacks.copy.settings.of') }}</x-design::body>

    @forelse ($this->answer()->settings as $setting)
        <x-operator::a-stack-setting
            :setting="$setting"
            :changing="$this->changing"
            :going="$this->howItIsGoing()"
            :said="$this->whatItSays(ASettingChangeFollowed::InTheStore)"
            :offer-to-set="$this->offered(ChangingASetting::Set)"
            :offer-to-clear="$this->offered(ChangingASetting::Clear)"
            :settings-would-not-open="$this->theSettingsWouldNotOpen"
            :goes="$this->goes()"
            :typed="$this->typed"
            :to-move-alongside="$this->toMoveAlongside()"
        />
    @empty
        <x-design::note>{{ __('stacks.copy.settings.none') }}</x-design::note>
    @endforelse

    <x-operator::quiet-action :label="__('health.ask_again')" tap="askAgain()" />
</x-operator::content>
@else
    <x-operator::what-stopped-the-reading
        :settings-would-not-open="$this->theSettingsWouldNotOpen"
        :went="$this->answer()->went"
        :goes="$this->goes()"
    />
@endif

<x-operator::screen-closes :goes="$this->goes()" :here="$this->itsTab()" :marks="$this->marks()" />
