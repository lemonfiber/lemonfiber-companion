<native:column class="w-full gap-4 p-6">
    @if ($this->went()->isPaired())
        <native:text class="text-lg font-bold">{{ __('connection.paired', ['stack' => $this->called()]) }}</native:text>
        <native:text>{{ __('connection.paired_action') }}</native:text>
    @elseif ($this->went()->hasNowhereToWriteItDown())
        <native:text class="text-lg font-bold">{{ __('connection.no_store_on_this_device') }}</native:text>
        <native:text>{{ __('connection.no_store_on_this_device_action') }}</native:text>
    @elseif ($this->went()->couldNotOpenTheStore())
        <native:text class="text-lg font-bold">{{ __('connection.store_would_not_open') }}</native:text>
        <native:text>{{ __('connection.store_would_not_open_action') }}</native:text>
    @else
        <native:text class="text-lg font-bold">{{ __('connection.type_the_code') }}</native:text>
        <native:text>{{ __('connection.type_the_code_action') }}</native:text>

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
    @endif
</native:column>
