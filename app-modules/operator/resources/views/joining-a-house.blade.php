@use('Modules\Operator\Internal\WhereTheWayInIs')
<x-operator::screen-opens :title="__('household.joining.title')" :back="$this->hasAWayBack()" />

<x-operator::content>
    <x-design::note>{{ __('onboarding.step', ['step' => $this->at->step(), 'of' => $this->at->ofHowMany()]) }}</x-design::note>

    @if ($this->at === WhereTheWayInIs::FindingTheHouse)
        <x-design::title>{{ __($this->findsTheHouse()->said()) }}</x-design::title>
        <x-design::body>{{ __($this->findsTheHouse()->explained()) }}</x-design::body>

        @if ($this->nothingWasScanned())
            <x-design::notice tone="unknown">
                <x-design::strong>{{ __($this->whyNothingCameBack()) }}</x-design::strong>
                <x-design::body>{{ __($this->whatToDoAboutTheCamera()) }}</x-design::body>
            </x-design::notice>
        @elseif ($this->codeWasUnreadable)
            <x-design::notice>
                <x-design::strong>{{ __('household.joining.code_unreadable') }}</x-design::strong>
                <x-design::body>{{ __('household.joining.code_unreadable_action') }}</x-design::body>
            </x-design::notice>
        @elseif ($this->notKept)
            <x-design::notice>
                <x-design::strong>{{ __('household.joining.not_kept') }}</x-design::strong>
                <x-design::body>{{ __('household.joining.not_kept_action') }}</x-design::body>
            </x-design::notice>
        @endif

        <x-design::note>{{ __('device.camera_reason') }}</x-design::note>
        <x-design::action label="{{ __('household.joining.scan') }}" tap="findTheHouse()" />
    @else
        <x-design::title>{{ __('household.joining.signing_in') }}</x-design::title>
        <x-design::body>{{ __('household.joining.signing_in_explained') }}</x-design::body>

        @if ($this->stoodInTheWay() !== null)
            <x-design::notice>
                <x-design::strong>{{ __($this->stoodInTheWay()->said()) }}</x-design::strong>
                <x-design::body>{{ __($this->stoodInTheWay()->remedy()) }}</x-design::body>
            </x-design::notice>
        @endif

        <x-design::card>
            <native:outlined-text-input
                native:model="theirName"
                label="{{ __('household.joining.your_name') }}"
                autocorrect="off"
                autocapitalize="none"
            />

            <native:outlined-text-input
                native:model="typed"
                label="{{ __('household.joining.your_password') }}"
                keyboard="password"
                secure
            />

            <x-design::action label="{{ __('household.joining.sign_in') }}" tap="signIn()" />
        </x-design::card>
    @endif
</x-operator::content>
