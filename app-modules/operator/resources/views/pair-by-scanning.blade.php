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
            native:model="called"
            label="{{ __('connection.name_label') }}"
            placeholder="{{ __('connection.name_placeholder') }}"
            supporting="{{ __('connection.name_this_stack') }}"
        />

        {{-- N4-R2 and N4-R3 together, and said before the prompt rather than
             after a refusal: what the camera is for, and what still works
             without it. An operator who reads this and declines anyway has
             chosen the typed road knowingly. --}}
        <native:text>{{ __('device.camera_reason') }}</native:text>
        <native:text>{{ __('device.camera_alternative') }}</native:text>

        @if ($this->nothingWasScanned())
            <native:text>{{ __($this->whyNothingCameBack()) }}</native:text>
            <native:text>{{ __($this->remedyForTheCamera()) }}</native:text>
        @elseif ($this->codeWasUnreadable())
            <native:text>{{ __('connection.scanned_code_is_unreadable') }}</native:text>
            <native:text>{{ __('connection.scanned_code_is_unreadable_action') }}</native:text>
        @endif

        <native:button
            label="{{ __('connection.open_the_camera') }}"
            :disabled="! $this->mayScan()"
            @tap="scan()"
        />
    @endunless
</native:column>
