@use('Modules\Kernel\Api\ChangingASetting')
<x-wayfinding::stack-opens :back="$this->hasAWayBack()" :title="$this->stack()->name()->shown()" :stacks="$this->stacksToChooseFrom()" :choosing="$this->choosingAStack" />

@if ($this->answer()->went->cameBack())
<x-operator::content>
@if ($this->answer()->waits)
    <x-design::standing :said="__('plugins.settings.own_reading')" tone="working" />
@else
    <x-operator::heading>{{ __('navigation.menu.own_settings') }}</x-operator::heading>
    <x-design::body>{{ __('plugins.settings.own_of') }}</x-design::body>
    <x-operator::note>{{ __('plugins.settings.secrets_at_terminal') }}</x-operator::note>

    @forelse ($this->answer()->owners as $owner)
        <x-operator::heading>{{ __($owner->whose->key, $owner->whose->filling) }}</x-operator::heading>
        @forelse ($owner->settings as $setting)
            <x-operator::a-stack-setting
                :setting="$setting"
                :changing="$this->changing"
                :going="$this->howItIsGoing()"
                :offer-to-set="$this->offered(ChangingASetting::Set)"
                :offer-to-clear="$this->offered(ChangingASetting::Clear)"
                :settings-would-not-open="$this->theSettingsWouldNotOpen"
                :goes="$this->goes()"
            />
        @empty
            <x-design::note>{{ __('plugins.settings.own_no_settings') }}</x-design::note>
        @endforelse
        @forelse ($owner->secrets as $secret)
            <x-operator::a-secret :secret="$secret" />
        @empty
            <x-design::note>{{ __('plugins.settings.own_no_secrets') }}</x-design::note>
        @endforelse
    @empty
        <x-design::body>{{ __('plugins.settings.own_none') }}</x-design::body>
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

@if ($this->waitsForTheNextFrame())
    <x-design::the-next-frame />
@endif

<x-operator::screen-closes :goes="$this->goes()" :here="$this->itsTab()" :marks="$this->marks()" />
