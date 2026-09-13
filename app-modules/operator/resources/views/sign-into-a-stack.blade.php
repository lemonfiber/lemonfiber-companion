<native:column class="w-full gap-4 p-6">
    @if ($this->went()->isSignedIn())
        <native:text class="text-lg font-bold">{{ __('connection.signed_in', ['stack' => $this->stack()->name()->shown()]) }}</native:text>
        <native:text>{{ __('connection.signed_in_action') }}</native:text>
    @elseif ($this->went()->hasNowhereToKeepIt())
        <native:text class="text-lg font-bold">{{ __('connection.no_store_for_a_session') }}</native:text>
        <native:text>{{ __('connection.no_store_for_a_session_action') }}</native:text>
    @elseif ($this->went()->couldNotOpenTheStore())
        <native:text class="text-lg font-bold">{{ __('connection.session_would_not_keep') }}</native:text>
        <native:text>{{ __('connection.session_would_not_keep_action') }}</native:text>
    @else
        <native:text class="text-lg font-bold">{{ __('connection.sign_in_to', ['stack' => $this->stack()->name()->shown()]) }}</native:text>
        <native:text>{{ __('connection.sign_in_action') }}</native:text>

        @unless ($this->went()->isNotYet())
            <native:text class="font-bold">{{ __($this->went()->said()) }}</native:text>
            <native:text>{{ __($this->went()->remedy()) }}</native:text>
        @endunless

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
