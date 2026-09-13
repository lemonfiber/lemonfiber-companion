<native:column class="w-full gap-4 p-6">
    <native:text class="text-lg font-bold">{{ __($this->went()->said(), ['stack' => $this->stack()->name()->shown()]) }}</native:text>
    <native:text>{{ __($this->went()->remedy()) }}</native:text>

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
