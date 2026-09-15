<native:column class="w-full gap-4 p-6">
    <native:text class="text-lg font-bold">{{ $this->stack()->name()->shown() }}</native:text>

    @unless ($this->answer()->isSignedIn)
        {{-- N1-R44: the session has ended, so nothing was asked and there is
             nothing to report. The remedy is a screen rather than a sentence. --}}
        <native:text>{{ __('connection.session_has_ended') }}</native:text>
        <native:button
            label="{{ __('connection.sign_in') }}"
            @navigate="$this->goes()->signIn()"
        />
    @elseif ($this->answer()->met !== '')
        {{-- N1-R10: what stood in the way, and what to do about it, both off
             the obstacle so this screen cannot describe a condition differently
             from the one beside it. --}}
        <native:text class="font-bold">{{ __($this->answer()->met) }}</native:text>
        <native:text>{{ __($this->answer()->remedy) }}</native:text>

        {{-- N1-R3: the action is offered and the failure reported, rather than
             the action being taken away because the machine is unreachable. --}}
        <native:button label="{{ __('health.ask_again') }}" @tap="again()" />
    @else
        {{-- How many, said before the list. An operator who opened this because
             something looked unfamiliar wants the count before the rows. --}}
        <native:text class="font-bold">
            {{ trans_choice('health.undeclared_count', $this->howMany()) }}
        </native:text>

        {{-- N2-R21: what these are, said once and above them. The sentence is
             the whole point of the screen — a list of names an operator does
             not recognise, with nothing saying why they are here, is what this
             replaces. --}}
        <native:text class="text-sm">{{ __('health.undeclared_explained') }}</native:text>

        @forelse ($this->answer()->running as $container)
            <native:column class="w-full gap-1">
                {{-- N2-R21 asks for each to be named and for what it is running
                     to be stated, and for no verb against it. There is no
                     button on this row and no identifier on the value that a
                     verb would accept. --}}
                <native:text class="font-bold">{{ $container->named }}</native:text>
                <native:text class="text-sm">{{ $container->describes }}</native:text>
                <native:text class="text-sm">{{ __($container->runs) }}</native:text>
            </native:column>
        @empty
            {{-- Not the same screen as a machine that could not be asked.
                 Nothing unaccounted for is the answer the operator wants, and
                 saying so is what tells it apart from the obstacle branch. --}}
            <native:text class="font-bold">{{ __('health.nothing_undeclared') }}</native:text>
            <native:text>{{ __('health.nothing_undeclared_action') }}</native:text>
        @endforelse
    @endunless

    <native:button label="{{ __('health.back_to_the_stack') }}" @navigate="$this->goes()->health()" />
</native:column>
