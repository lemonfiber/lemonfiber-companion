@use('Modules\Stacks\Api\AStacksScreen')
@use('Modules\Kernel\Api\ChangingASetting')
<x-wayfinding::stack-opens :back="$this->hasAWayBack()" :title="$this->stack()->name()->shown()" :stacks="$this->stacksToChooseFrom()" :choosing="$this->choosingAStack" />

@if ($this->answer()->went->cameBack())
<x-operator::content>
@if ($this->answer()->waiting === null)
    <x-operator::heading>{{ $this->service()->named() }}</x-operator::heading>
    <x-design::body>{{ __('plugins.waiting.not_held_back', ['service' => $this->service()->named()]) }}</x-design::body>
    <x-design::link :label="__('navigation.services')" :goes="$this->goes()->to(AStacksScreen::Services)" />
@else
    <x-operator::heading>{{ $this->service()->named() }}</x-operator::heading>
    <x-operator::a-service-waiting :waiting="$this->answer()->waiting" />

    @forelse ($this->answer()->settings as $setting)
        <x-operator::a-stack-setting
            :setting="$setting"
            :changing="$this->changing"
            :going="$this->howItIsGoing()"
            :offer-to-set="$this->offered(ChangingASetting::Set)"
            :offer-to-clear="$this->offered(ChangingASetting::Clear)"
            :settings-would-not-open="$this->theSettingsWouldNotOpen"
            :goes="$this->goes()"
            :typed="$this->typed"
            :to-move-alongside="$this->toMoveAlongside()"
        />
    @empty
        <x-design::body>{{ __('plugins.waiting.nothing_to_set') }}</x-design::body>
    @endforelse

    <x-operator::quiet-action :label="__('health.ask_again')" tap="askAgain()" />
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
