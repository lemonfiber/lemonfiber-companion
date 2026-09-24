<x-operator::screen-opens :title="$this->stack()->name()->shown()" />

<x-operator::content>
    <x-operator::heading>{{ __($this->went()->said(), ['stack' => $this->stack()->name()->shown()]) }}</x-operator::heading>
    <native:text>{{ __($this->went()->remedy()) }}</native:text>

    @if ($this->isSignedIn())
        {{-- Straight to what they came for, rather than telling them where to
             find it. An app that says "you can reach it from the main screen"
             is an app asking somebody to navigate on its behalf. --}}
        <x-operator::action label="{{ __('health.see_how_it_is') }}" :goes="$this->onwardsTo()" />
    @endif

    @if ($this->mayStartOver())
        {{-- The other half of a `Guided` standing. The remedy was instructions,
             and somebody who has gone and followed them comes back to a screen
             still holding what it was told before they did. --}}
        <x-operator::action label="{{ __('connection.start_over') }}" tap="startOver()" />
    @endif

    @if ($this->mayTry())
        <native:outlined-text-input
            native:model="typed"
            label="{{ __('connection.password_label') }}"
            placeholder="{{ __('connection.password_placeholder') }}"
            keyboard="password"
            secure
        />

        <x-operator::action label="{{ __($this->went()->isWorthAnotherAttempt() ? 'connection.try_that_again' : 'connection.sign_in') }}" :disabled="! $this->mayOffer()" tap="offer()" />
    @endif
</x-operator::content>
