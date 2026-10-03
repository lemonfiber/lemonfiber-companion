<x-wayfinding::stack-opens :title="$this->stack()->name()->shown()" :stacks="$this->stacksToChooseFrom()" :choosing="$this->choosingAStack" />

<x-operator::content>
    <x-design::title>{{ __($this->went()->said(), ['stack' => $this->stack()->name()->shown()]) }}</x-design::title>
    <x-design::body>{{ __($this->went()->remedy()) }}</x-design::body>
    @if ($this->went()->isPutRightInTheAppsSettings())
        <x-design::action label="{{ __('connection.open_settings') }}" tap="openTheAppsSettings()" />
        @if ($this->theSettingsWouldNotOpen)
            <x-design::note>{{ __('connection.settings_would_not_open') }}</x-design::note>
        @endif
    @endif

    @if ($this->isSignedIn())
        {{-- Straight to what they came for, rather than telling them where to
             find it. An app that says "you can reach it from the main screen"
             is an app asking somebody to navigate on its behalf. --}}
        <x-design::action label="{{ __('health.see_how_it_is') }}" :goes="$this->onwardsTo()" />
    @endif

    @if ($this->mayStartOver())
        {{-- The other half of a `Guided` standing. The remedy was instructions,
             and somebody who has gone and followed them comes back to a screen
             still holding what it was told before they did. --}}
        <x-design::action label="{{ __('connection.start_over') }}" tap="startOver()" />
    @endif

    @if ($this->mayPairAgain())
        {{-- A new code from the machine replaces the certificate pinned for
             this stack; the password field stays away, because whatever is
             answering is not the machine that was paired. --}}
        <x-operator::action label="{{ __('connection.pair_again') }}" :goes="$this->pairAgainAt()" />
    @endif

    @if ($this->mayTry())
        <x-design::card>
            <native:outlined-text-input
                native:model="typed"
                label="{{ __('connection.password_label') }}"
                placeholder="{{ __('connection.password_placeholder') }}"
                keyboard="password"
                secure
            />

            <x-design::action label="{{ __($this->went()->isWorthAnotherAttempt() ? 'connection.try_that_again' : 'connection.sign_in') }}" :disabled="! $this->mayOffer()" tap="offer()" />
        </x-design::card>
    @endif
</x-operator::content>
