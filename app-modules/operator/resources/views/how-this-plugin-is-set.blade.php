@use('Modules\Kernel\Api\ChangingASetting')
@use('Modules\Stacks\Api\AStacksScreen')
<x-wayfinding::stack-opens :back="$this->hasAWayBack()" :title="$this->stack()->name()->shown()" :stacks="$this->stacksToChooseFrom()" :choosing="$this->choosingAStack" />

@if ($this->answer()->went->cameBack())
<x-operator::content>
    <x-operator::heading>{{ __('plugins.settings.heading') }}</x-operator::heading>

@if ($this->answer()->read === null)
    <x-operator::emphasis>{{ __('plugins.settings.not_installed') }}</x-operator::emphasis>
    <x-operator::quiet-action :label="__('plugins.settings.back')" :goes="$this->goes()->to(AStacksScreen::Plugins)" />
@else
    <x-design::body>{{ __('plugins.settings.of', ['name' => $this->answer()->read->plugin]) }}</x-design::body>

    @forelse ($this->answer()->read->settings as $setting)
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
        <x-design::body>{{ __('plugins.settings.none') }}</x-design::body>
    @endforelse

    <x-operator::heading>{{ __('plugins.settings.secrets') }}</x-operator::heading>
    <x-operator::note>{{ __('plugins.settings.secrets_at_terminal') }}</x-operator::note>
    @forelse ($this->answer()->read->secrets as $secret)
        <x-operator::a-secret :secret="$secret" />
    @empty
        <x-design::body>{{ __('plugins.settings.no_secrets') }}</x-design::body>
    @endforelse

    {{-- Each credential of another service granted to it, by its setting and never its value. --}}
    <x-operator::heading>{{ __('plugins.settings.grants') }}</x-operator::heading>
    @forelse ($this->answer()->read->grants as $grant)
        <x-design::card>
            <x-design::strong>{{ __('stacks.credentials.grants.of', ['credential' => $grant->credential, 'whose' => $grant->whose]) }}</x-design::strong>
            <x-operator::the-grant :grant="$grant" />
        </x-design::card>
    @empty
        <x-design::body>{{ __('plugins.settings.no_grants') }}</x-design::body>
    @endforelse
    @unless ($this->answer()->read->grants === [])
        <x-operator::note>{{ __('plugins.settings.grants_ended_with_the_passwords') }}</x-operator::note>
        <x-operator::quiet-action :label="__('plugins.settings.to_the_passwords')" :goes="$this->goes()->to(AStacksScreen::Credentials)" />
    @endunless

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
