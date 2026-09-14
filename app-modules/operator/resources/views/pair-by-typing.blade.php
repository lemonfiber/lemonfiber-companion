<native:column class="w-full gap-4 p-6">
    <native:text class="text-lg font-bold">{{ __($this->headline(), ['stack' => $this->called()]) }}</native:text>
    <native:text>{{ __($this->supporting()) }}</native:text>

    @if ($this->went()->isPaired())
        {{-- Pairing is not signing in: the machine has been introduced and
             this device holds no session for it. So the way onwards is the
             password, not the report. --}}
        <native:button
            label="{{ __('connection.sign_in') }}"
            @navigate="{{ $this->onwardsTo() }}"
        />
    @endif

    @unless ($this->went()->isPaired())
        <native:outlined-text-input
            native:model="typed"
            label="{{ __('connection.code_label') }}"
            placeholder="{{ __('connection.code_placeholder') }}"
            supporting="{{ __($this->supportingTheCode()) }}"
            :error="$this->isUnreadable() || $this->hasExpired()"
            multiline
        />

        <native:outlined-text-input
            native:model="called"
            label="{{ __('connection.name_label') }}"
            placeholder="{{ __('connection.name_placeholder') }}"
            supporting="{{ __('connection.name_this_stack') }}"
        />

        @if ($this->isComparing())
            <native:text>{{ __('connection.compare_the_fingerprint') }}</native:text>
            <native:text class="text-xl font-bold">{{ $this->toCompare() }}</native:text>
        @endif

        <native:button
            label="{{ __('connection.it_matches') }}"
            :disabled="! $this->mayPair()"
            @tap="confirm()"
        />
    @endunless
</native:column>
