@use('Modules\Operator\Internal\WhereTheWayInIs')
<x-operator::screen-opens :title="__('household.joining.title')" :back="$this->hasAWayBack()" />

<x-operator::content>
    <x-design::note>{{ __('onboarding.step', ['step' => $this->at->step(), 'of' => $this->at->ofHowMany()]) }}</x-design::note>

    @if ($this->at === WhereTheWayInIs::FindingTheHouse && $this->offeredAt !== '')
        {{-- A link anybody can send names the machine it connects to, so the
             person says whether somebody in their house sent it before
             anything is kept or any password is asked for. --}}
        <x-design::title>{{ __('household.joining.is_it_yours') }}</x-design::title>
        <x-design::body>{{ __('household.joining.is_it_yours_explained') }}</x-design::body>
        <x-design::card>
            <x-design::verbatim>{{ $this->offeredAt }}</x-design::verbatim>
        </x-design::card>
        <x-design::action label="{{ __('household.joining.it_is_yours') }}" tap="confirmTheHouse()" />
        <x-operator::quiet-action label="{{ __('household.joining.it_is_not_yours') }}" tap="forgetTheHouse()" />
    @elseif ($this->at === WhereTheWayInIs::FindingTheHouse)
        <x-design::title>{{ __($this->findsTheHouse()->said()) }}</x-design::title>
        <x-design::body>{{ __($this->findsTheHouse()->explained()) }}</x-design::body>

        @if ($this->nothingWasScanned())
            <x-design::notice tone="unknown">
                <x-design::strong>{{ __($this->whyNothingCameBack()) }}</x-design::strong>
                <x-design::body>{{ __($this->whatToDoAboutTheCamera()) }}</x-design::body>
            </x-design::notice>
        @endif
        @if ($this->met !== null)
            <x-design::notice>
                <x-design::strong>{{ __($this->met->said()) }}</x-design::strong>
                <x-design::body>{{ __($this->met->remedy()) }}</x-design::body>
            </x-design::notice>
        @endif

        <x-design::note>{{ __('device.camera_reason') }}</x-design::note>
        <x-design::action label="{{ __('household.joining.scan') }}" tap="findTheHouse()" />
    @else
        @if ($this->choosing)
            <x-design::title>{{ __('household.joining.choosing') }}</x-design::title>
            <x-design::body>{{ __('household.joining.choosing_explained') }}</x-design::body>
        @else
            <x-design::title>{{ __('household.joining.signing_in') }}</x-design::title>
            <x-design::body>{{ __('household.joining.signing_in_explained') }}</x-design::body>
        @endif

        @if ($this->stoodInTheWay() !== null && $this->told !== null && $this->told->wasSaid())
            <x-design::notice>
                <x-design::strong>{{ $this->told->sentence() }}</x-design::strong>
                <x-design::body>{{ $this->told->remedy() }}</x-design::body>
            </x-design::notice>
        @elseif ($this->stoodInTheWay() !== null)
            <x-design::notice>
                <x-design::strong>{{ __($this->stoodInTheWay()->said()) }}</x-design::strong>
                <x-design::body>{{ __($this->stoodInTheWay()->remedy()) }}</x-design::body>
            </x-design::notice>
        @endif

        <x-design::card>
            <native:outlined-text-input
                native:model="theirName"
                label="{{ __('household.joining.your_name') }}"
                content-type="username"
                autocorrect="off"
                autocapitalize="none"
            />

            <native:outlined-text-input
                native:model="typed"
                label="{{ $this->choosing ? __('household.joining.chosen_password') : __('household.joining.your_password') }}"
                content-type="{{ $this->choosing ? 'new-password' : 'password' }}"
                keyboard="password"
                secure
            />

            <x-design::action label="{{ $this->choosing ? __('household.joining.choose') : __('household.joining.sign_in') }}" tap="signIn()" />
        </x-design::card>
    @endif
</x-operator::content>
