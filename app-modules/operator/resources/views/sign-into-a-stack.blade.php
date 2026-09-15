<native:column class="w-full gap-4 p-6">
    <native:text class="text-lg font-bold">{{ __($this->went()->said(), ['stack' => $this->stack()->name()->shown()]) }}</native:text>
    <native:text>{{ __($this->went()->remedy()) }}</native:text>

    @if ($this->isSignedIn())
        {{-- Straight to what they came for, rather than telling them where to
             find it. An app that says "you can reach it from the main screen"
             is an app asking somebody to navigate on its behalf. --}}
        <native:button
            label="{{ __('health.see_how_it_is') }}"
            @navigate="$this->onwardsTo()"
        />
    @endif

    @if ($this->mayStartOver())
        {{-- The other half of a `Guided` standing. The remedy was instructions,
             and somebody who has gone and followed them comes back to a screen
             still holding what it was told before they did. --}}
        <native:button label="{{ __('connection.start_over') }}" @tap="startOver()" />
    @endif

    @if ($this->mayTry())
        <native:outlined-text-input
            native:model="typed"
            label="{{ __('connection.password_label') }}"
            placeholder="{{ __('connection.password_placeholder') }}"
            keyboard="password"
            secure
        />

        <native:button
            label="{{ __($this->went()->isWorthAnotherAttempt() ? 'connection.try_that_again' : 'connection.sign_in') }}"
            :disabled="! $this->mayOffer()"
            @tap="offer()"
        />
    @endif
</native:column>
