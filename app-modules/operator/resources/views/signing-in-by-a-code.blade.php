@use('Modules\Connection\Api\HowTheSignInWent')
<x-wayfinding::stack-opens :back="$this->hasAWayBack()" :title="__('connection.code_title')" :stacks="$this->stacksToChooseFrom()" :choosing="$this->choosingAStack" />

<x-operator::content>
    @if ($this->theCode() !== null)
        <x-design::title>{{ __('connection.code_approve') }}</x-design::title>
        <x-design::body>{{ __('connection.code_explained') }}</x-design::body>
        <x-design::card>
            <x-design::heading>{{ $this->theCode()->code }}</x-design::heading>
            <x-design::action :label="__('connection.code_copy')" tap="copyTheCode()" />
            @if ($this->copied !== null)
                <x-design::note>{{ __($this->copied) }}</x-design::note>
            @endif
        </x-design::card>
        <x-design::card>
            <x-design::action :label="__('connection.code_open')" tap="openThePage()" />
            @if ($this->pageWouldNotOpen)
                <x-design::note>{{ __('connection.code_page_would_not_open') }}</x-design::note>
            @endif
            <x-design::scannable :rows="$this->theCode()->squares" :missing="__('connection.code_no_squares')" />
            <x-design::note>{{ __('connection.code_scan') }}</x-design::note>
        </x-design::card>
        <x-design::note>{{ __('connection.code_lapses', ['time' => $this->theCode()->lapsesAt]) }}</x-design::note>
        @if ($this->went !== HowTheSignInWent::NotYet)
            <x-design::notice>
                <x-design::strong>{{ __($this->went->said(), ['stack' => $this->stack()->name()->shown()]) }}</x-design::strong>
                <x-design::body>{{ __($this->went->remedy()) }}</x-design::body>
            </x-design::notice>
        @endif
    @elseif ($this->told !== null)
        @if ($this->told->wasSaid())
            <x-design::title>{{ $this->told->sentence() }}</x-design::title>
            <x-design::body>{{ $this->told->remedy() }}</x-design::body>
        @elseif ($this->byPassword)
            <x-design::title>{{ __('connection.code_signs_in_by_password') }}</x-design::title>
        @else
            <x-design::title>{{ __('connection.code_no_longer_waits') }}</x-design::title>
        @endif
        @if ($this->byPassword)
            <x-design::action :label="__('connection.code_by_password')" tap="usePassword()" />
        @else
            <x-design::action :label="__('connection.code_new')" tap="askForACode()" />
        @endif
    @else
        <x-design::title>{{ __($this->went->said(), ['stack' => $this->stack()->name()->shown()]) }}</x-design::title>
        <x-design::body>{{ __($this->went->remedy()) }}</x-design::body>
        <x-operator::the-wait :wait="$this->wait" />
        @if ($this->went->isPutRightInTheAppsSettings())
            <x-design::action :label="__('connection.open_settings')" tap="openTheAppsSettings()" />
            @if ($this->theSettingsWouldNotOpen)
                <x-design::note>{{ __('connection.settings_would_not_open') }}</x-design::note>
            @endif
        @endif
        @if (! $this->went->isSignedIn())
            <x-design::action :label="__('connection.code_new')" tap="askForACode()" />
        @endif
    @endif
</x-operator::content>
