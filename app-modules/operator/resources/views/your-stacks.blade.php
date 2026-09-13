<native:column class="w-full gap-4 p-6">
    @forelse ($this->configured() as $stack)
        <native:column class="w-full gap-1">
            <native:button
                label="{{ $stack->name()->shown() }}"
                @navigate="{{ $this->tappingGoesTo($stack) }}"
            />
            <native:text>
                {{ __($this->isSignedInto($stack) ? 'connection.stack_is_open' : 'connection.stack_wants_a_password') }}
            </native:text>
        </native:column>
    @empty
        <native:text class="text-lg font-bold">{{ __('connection.no_stacks') }}</native:text>
        <native:text>{{ __('connection.setup_is_at_the_machine') }}</native:text>
        <native:text>{{ __('connection.no_stacks_action') }}</native:text>
    @endforelse

    @if ($this->sharingWent() !== '')
        <native:text class="font-bold">{{ __($this->sharingWent()) }}</native:text>
        <native:text>{{ __($this->sharingRemedy()) }}</native:text>
    @endif

    <native:button label="{{ __('connection.pair') }}" @navigate='/pair/scanned' />
    <native:button label="{{ __('connection.pair_by_typing') }}" @navigate='/pair/typed' />

    {{-- N4-R13: assembled for the operator to send, and not sent by the app.
         On this screen because it is the one reachable from anywhere and the
         one that works when nothing else does — a stack that cannot be reached
         is exactly when somebody needs to ask for help. --}}
    <native:button label="{{ __('device.share_diagnostics') }}" @tap="share()" />
</native:column>
