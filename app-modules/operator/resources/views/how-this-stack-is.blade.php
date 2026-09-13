<native:column class="w-full gap-4 p-6">
    <native:text class="text-lg font-bold">{{ $this->stack()->name()->shown() }}</native:text>

    @unless ($this->isSignedIn())
        {{-- N1-R44: the session has ended, so nothing was asked and there is
             nothing to report. The remedy is a screen rather than a sentence. --}}
        <native:text>{{ __('connection.session_has_ended') }}</native:text>
        <native:button
            label="{{ __('connection.sign_in') }}"
            @navigate="{{ $this->signInAt() }}"
        />
    @elseif ($this->met() !== '')
        <native:text class="font-bold">{{ __($this->met()) }}</native:text>
        <native:text>{{ __($this->remedy()) }}</native:text>
    @else
        <native:text class="font-bold">{{ __($this->overall()) }}</native:text>

        @forelse ($this->findings() as $finding)
            <native:column class="w-full gap-1">
                <native:text class="font-bold">{{ $finding->title() }}</native:text>
                <native:text>{{ __($finding->conclusion()->saidOnTheScreen()) }}</native:text>
            </native:column>
        @empty
            <native:text>{{ __('health.nothing_to_report') }}</native:text>
        @endforelse
    @endunless
</native:column>
