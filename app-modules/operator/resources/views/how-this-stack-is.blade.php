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

        <native:button label="{{ __('health.ask_again') }}" @tap="again()" />
    @else
        <native:text class="font-bold">{{ __($this->overall()) }}</native:text>

        @forelse ($this->findings() as $finding)
            <native:column class="w-full gap-1">
                <native:text class="text-sm">{{ __($finding->about) }}</native:text>
                <native:text class="font-bold">{{ $finding->title }}</native:text>
                <native:text>{{ __($finding->verdict) }}</native:text>

                {{-- N2-R3: what the core said about it, in the core's own
                     words. Rendered rather than translated — these are the
                     machine's sentences about the machine, and putting them
                     through the catalogue would mean this app inventing a
                     line for a check it has never heard of. --}}
                @if ($finding->explainsItself())
                    <native:text>{{ $finding->meaning }}</native:text>
                    <native:text class="text-sm">{{ $finding->code }}</native:text>

                    {{-- A failure may carry no remedy at all, and that is a
                         sentence rather than blank space: the operator is
                         being told the machine knows what is wrong and has
                         nothing to suggest, which is what sends them to the
                         machine itself. --}}
                    @forelse ($finding->remedies as $remedy)
                        <native:text>{{ $remedy->action() }}</native:text>
                    @empty
                        <native:text>{{ __('health.nothing_to_try') }}</native:text>
                    @endforelse
                @endif
            </native:column>
        @empty
            <native:text>{{ __('health.no_findings') }}</native:text>
        @endforelse

        {{-- Under the findings rather than above them: somebody who has just
             fixed something scrolls to the end of what was wrong, and that is
             where they want to ask whether it took. --}}
        <native:button label="{{ __('health.ask_again') }}" @tap="again()" />
    @endunless
</native:column>
