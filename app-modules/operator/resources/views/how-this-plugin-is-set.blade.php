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
        <x-design::card>
            <x-design::strong>{{ $setting->name }}</x-design::strong>
            @if ($setting->whose !== null)
                <x-design::note>{{ __($setting->whose->key, $setting->whose->filling) }}</x-design::note>
            @endif
            @if ($setting->isSet)
                <x-design::body>{{ __('plugins.settings.set_by_you') }}</x-design::body>
                <x-design::body>{{ $setting->value }}</x-design::body>
            @else
                <x-design::body>{{ __('plugins.settings.default') }}</x-design::body>
            @endif
            @forelse ($setting->readers as $reader)
                @if ($reader->declaresADefault)
                    <x-design::note>{{ __('plugins.settings.read_by_default', ['service' => $reader->service, 'variable' => $reader->variable, 'default' => $reader->default]) }}</x-design::note>
                @else
                    <x-design::note>{{ __('plugins.settings.read_by', ['service' => $reader->service, 'variable' => $reader->variable]) }}</x-design::note>
                @endif
            @empty
                {{-- Unreachable: a setting is listed here because one of the plugin's services reads it. --}}
            @endforelse

            @if ($this->changing?->named === $setting->name)
                @unless ($this->changing->toDefault)
                    <native:outlined-text-input native:model="typed" label="{{ __('plugins.settings.value') }}" supporting="{{ $setting->name }}" />
                    <x-operator::offered-action :label="__('plugins.settings.rehearse')" tap="rehearse()" :offer="$this->offered(ChangingASetting::Set)" />
                @endunless

                @if (! $this->howItIsGoing()->stands->went->cameBack())
                    <x-operator::what-stood-in-the-way
                        :settings-would-not-open="$this->theSettingsWouldNotOpen"
                        :went="$this->howItIsGoing()->stands->went"
                        :goes="$this->goes()"
                    />
                @elseif ($this->howItIsGoing()->stands->isWorking())
                    <x-design::standing :said="__('plugins.settings.working')" tone="working" />
                @elseif ($this->howItIsGoing()->stands->hasEnded())
                    <x-design::notice tone="unknown">
                        <x-design::strong>{{ __('plugins.settings.no_outcome') }}</x-design::strong>
                    </x-design::notice>
                    <x-design::action :label="__('plugins.settings.start_again')" :answers-to="__('plugins.settings.start_again_on', ['name' => $setting->name])" tap="startAgain()" />
                @elseif ($this->howItIsGoing()->refused !== null)
                    <x-design::notice>
                        <x-design::strong>{{ __('plugins.settings.refused') }}</x-design::strong>
                        <x-operator::refused-in-its-words :refused="$this->howItIsGoing()->refused" />
                    </x-design::notice>
                    <x-design::action :label="__('plugins.settings.start_again')" :answers-to="__('plugins.settings.start_again_on', ['name' => $setting->name])" tap="startAgain()" />
                @elseif ($this->howItIsGoing()->change !== null)
                    <x-design::notice>
                        <x-design::strong>{{ __($this->howItIsGoing()->change->didSaid) }}</x-design::strong>
                    </x-design::notice>
                    @if ($this->howItIsGoing()->change->toDefault)
                        <x-design::body>{{ __('plugins.settings.to_default') }}</x-design::body>
                    @else
                        <x-design::body>{{ __('plugins.settings.to', ['value' => $this->howItIsGoing()->change->value]) }}</x-design::body>
                    @endif
                    @if ($this->howItIsGoing()->change->wasSet)
                        <x-design::note>{{ __('plugins.settings.was', ['was' => $this->howItIsGoing()->change->was]) }}</x-design::note>
                    @else
                        <x-design::note>{{ __('plugins.settings.was_default') }}</x-design::note>
                    @endif
                    @if ($this->howItIsGoing()->change->restarts === '')
                        <x-design::body>{{ __('plugins.settings.restarts_nothing') }}</x-design::body>
                    @else
                        <x-design::body>{{ __('plugins.settings.restarts', ['services' => $this->howItIsGoing()->change->restarts]) }}</x-design::body>
                    @endif
                    @unless ($this->howItIsGoing()->change->regenerates === '')
                        <x-design::note>{{ __('plugins.settings.regenerates', ['services' => $this->howItIsGoing()->change->regenerates]) }}</x-design::note>
                    @endunless
                    @if ($this->howItIsGoing()->change->consequence !== '')
                        <x-design::note>{{ __('plugins.settings.consequence', ['consequence' => $this->howItIsGoing()->change->consequence]) }}</x-design::note>
                    @endif
                    @if ($this->howItIsGoing()->change->unrestarted !== '')
                        <x-design::note>{{ __('plugins.settings.unrestarted', ['why' => $this->howItIsGoing()->change->unrestarted]) }}</x-design::note>
                    @endif
                    @if ($this->howItIsGoing()->change->mayBeAgreed)
                        @if ($this->howItIsGoing()->change->toDefault)
                            <x-operator::offered-action :label="__('plugins.settings.agree_back')" tap="agree()" :offer="$this->offered(ChangingASetting::Clear)" />
                        @else
                            <x-operator::offered-action :label="__('plugins.settings.agree')" tap="agree()" :offer="$this->offered(ChangingASetting::Set)" />
                        @endif
                    @endif
                @endif

                <x-operator::quiet-action :label="__('plugins.settings.never')" tap="never()" />
            @else
                <x-operator::offered-action :label="__('plugins.settings.change')" :answers-to="__('plugins.settings.change_it', ['name' => $setting->name])" :tap="$this->tapping('change', $setting->name)" :offer="$this->offered(ChangingASetting::Set)" />
                @if ($setting->isSet)
                    <x-operator::offered-action :label="__('plugins.settings.back_to_default')" :answers-to="__('plugins.settings.back_to_default_it', ['name' => $setting->name])" :tap="$this->tapping('backToDefault', $setting->name)" :offer="$this->offered(ChangingASetting::Clear)" />
                @endif
            @endif
        </x-design::card>
    @empty
        <x-design::body>{{ __('plugins.settings.none') }}</x-design::body>
    @endforelse

    <x-operator::heading>{{ __('plugins.settings.secrets') }}</x-operator::heading>
    <x-operator::note>{{ __('plugins.settings.secrets_at_terminal') }}</x-operator::note>
    @forelse ($this->answer()->read->secrets as $secret)
        <x-design::card>
            <x-design::strong>{{ $secret->name }}</x-design::strong>
            @if ($secret->whose !== null)
                <x-design::note>{{ __($secret->whose->key, $secret->whose->filling) }}</x-design::note>
            @endif
            <x-design::body>{{ __($secret->heldSaid) }}</x-design::body>
            <x-design::note>{{ __($secret->fromSaid) }}</x-design::note>
            @forelse ($secret->readers as $reader)
                <x-design::note>{{ __('plugins.settings.read_by', ['service' => $reader->service, 'variable' => $reader->variable]) }}</x-design::note>
            @empty
                {{-- Unreachable: a secret is listed here because one of the plugin's services reads it. --}}
            @endforelse
        </x-design::card>
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
