@use('Modules\Operator\Internal\ASettingChangeFollowed')
@use('Modules\Kernel\Api\ChangingASetting')
@use('Modules\Kernel\Api\WhatToChange')
<x-wayfinding::stack-opens :back="$this->hasAWayBack()" :title="$this->stack()->name()->shown()" :stacks="$this->stacksToChooseFrom()" :choosing="$this->choosingAStack" />

@if ($this->answer()->went->cameBack())
<x-operator::content>
    <x-operator::heading>{{ __('navigation.menu.decided') }}</x-operator::heading>
    <x-design::body>{{ __('decided.intro') }}</x-design::body>
@if ($this->answer()->waits)
    <x-design::standing :said="__('decided.reading')" tone="working" />
@else

    @if ($this->answer()->opened !== null)
        <x-design::section :label="__('decided.opened.section', ['key' => $this->answer()->opened->key])">
            @if (! $this->answer()->opened->proven)
                <x-design::standing :said="__('decided.opened.unproven')" tone="attention" />
            @endif
            <x-design::body>{{ __('decided.opened.in_order') }}</x-design::body>
            @forelse ($this->answer()->opened->steps as $step)
                <x-operator::one-decision
                    :decision="$step"
                    :open="$this->changing() === $step->key"
                    :proposal="$this->proposalFor($step->key)"
                    :waiting="$this->landing()->waitsToChange($step->key)"
                    :landing-said="$this->landing()->saidAbout($step->key)" :landing-says="$this->whatItSays(ASettingChangeFollowed::OnceLanded)"
                    :offer="$this->offered($step->setting === null ? WhatToChange::Setting : ChangingASetting::Set)"
                    :going="$this->howItIsGoing()"
                    :said="$this->whatItSays(ASettingChangeFollowed::InTheStore)"
                    :settings-would-not-open="$this->theSettingsWouldNotOpen"
                    :goes="$this->goes()"
                />
            @empty
                <x-design::note>{{ __('decided.opened.nothing') }}</x-design::note>
            @endforelse
        </x-design::section>
    @endif

    @forelse ($this->answer()->decisions as $decision)
        <x-operator::one-decision
            :decision="$decision"
            :open="$this->changing() === $decision->key && ! ($this->answer()->opened?->among($decision->key) ?? false)"
            :proposal="$this->proposalFor($decision->key)"
            :waiting="$this->landing()->waitsToChange($decision->key)"
            :landing-said="$this->landing()->saidAbout($decision->key)" :landing-says="$this->whatItSays(ASettingChangeFollowed::OnceLanded)"
            :offer="$this->offered($decision->setting === null ? WhatToChange::Setting : ChangingASetting::Set)"
            :going="$this->howItIsGoing()"
            :said="$this->whatItSays(ASettingChangeFollowed::InTheStore)"
            :settings-would-not-open="$this->theSettingsWouldNotOpen"
            :goes="$this->goes()"
        />
    @empty
        <x-design::note>{{ __('decided.none') }}</x-design::note>
    @endforelse

    <x-design::card>
        <x-design::strong>{{ __('decided.autostart.heading') }}</x-design::strong>
        <x-design::body>{{ __($this->answer()->autostart->standsSaid) }}</x-design::body>
        <x-design::note>{{ __('decided.autostart.at_the_machine') }}</x-design::note>
        <x-design::verbatim>{{ $this->answer()->autostart->install }}</x-design::verbatim>
        <x-design::action :label="__('decided.copy')" :answers-to="__('decided.copy_install')" :tap="$this->tapping('copy', $this->answer()->autostart->install)" tone="tonal" />
        <x-design::verbatim>{{ $this->answer()->autostart->remove }}</x-design::verbatim>
        <x-design::action :label="__('decided.copy')" :answers-to="__('decided.copy_remove')" :tap="$this->tapping('copy', $this->answer()->autostart->remove)" tone="tonal" />
    </x-design::card>

    @if ($this->copied !== null)
        <x-design::note>{{ __($this->copied) }}</x-design::note>
    @endif

    <x-operator::quiet-action :label="$this->unfolded ? __('decided.another.fold') : __('decided.another.unfold')" tap="turnTheFold()" />
    @if ($this->unfolded)
        <x-design::body>{{ __('decided.another.explained') }}</x-design::body>
        <native:outlined-text-input native:model="named" label="{{ __('decided.another.name') }}" supporting="{{ __('decided.another.never_a_credential') }}" />
        <x-design::action :label="__('decided.another.open')" tap="another()" tone="tonal" />
        @if ($this->refusedName !== null)
            <x-design::notice>
                <x-design::strong>{{ __('decided.another.refused', ['key' => $this->refusedName]) }}</x-design::strong>
            </x-design::notice>
        @endif
        @if ($this->opensFromTheField())
            <native:outlined-text-input native:model="typed" label="{{ $this->changing() }}" supporting="{{ __('config.what_it_would_hold') }}" />
            <x-operator::the-review :key="$this->changing()" :proposal="$this->proposalFor($this->changing())" :waiting="$this->landing()->waitsToChange($this->changing())" :landing-said="$this->landing()->saidAbout($this->changing())" :landing-says="$this->whatItSays(ASettingChangeFollowed::OnceLanded)" />
        @endif
    @endif

    <x-operator::quiet-action :label="__('config.ask_again')" tap="askAgain()" />
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
