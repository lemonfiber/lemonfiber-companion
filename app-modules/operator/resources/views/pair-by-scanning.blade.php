<native:column class="w-full gap-4 p-6">
    <native:text class="text-lg font-bold">{{ __($this->headline(), ['stack' => $this->called()]) }}</native:text>
    <native:text>{{ __($this->supporting()) }}</native:text>

    @unless ($this->went()->isPaired())
        <native:outlined-text-input
            native:model="called"
            label="{{ __('connection.name_label') }}"
            placeholder="{{ __('connection.name_placeholder') }}"
            supporting="{{ __('connection.name_this_stack') }}"
        />

        <native:text>{{ __('device.camera_reason') }}</native:text>

        @if ($this->nothingWasScanned())
            <native:text>{{ __($this->whyNothingCameBack()) }}</native:text>
            @if ($this->settingsWouldHelp())
                <native:text>{{ __('connection.the_camera_is_not_permitted_action') }}</native:text>
            @else
                <native:text>{{ __('device.camera_alternative') }}</native:text>
            @endif
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
