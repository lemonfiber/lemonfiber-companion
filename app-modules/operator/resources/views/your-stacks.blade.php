<native:column class="w-full gap-4 p-6">
    @forelse ($this->configured() as $stack)
        <native:column class="w-full gap-1">
            <native:button
                label="{{ $stack->name()->shown() }}"
                @navigate="{{ $this->tappingGoesTo($stack) }}"
            />
            {{-- N2-R1: the verdict, which is what the app opens on. `N2` calls
                 the ordering its whole design — is anything wrong, then what,
                 then may I fix it from here — and a first screen that leads
                 with the names their owner gave their machines answers a
                 question nobody opened the app to ask.

                 N1-R9, N2-R13: it is held rather than asked, so it carries when
                 it was read and is never drawn as though it were current. The
                 age comes out of the same fold as the word, so a row cannot
                 have one without the other. --}}
            @if ($this->lastKnownOf($stack)->isKnown)
                <native:text class="font-bold">{{ __($this->lastKnownOf($stack)->said) }}</native:text>
                <native:text class="text-sm">
                    {{ __('health.stale', [
                        'ago' => trans_choice($this->lastKnownOf($stack)->agoSaid, $this->lastKnownOf($stack)->agoCount),
                    ]) }}
                </native:text>
            @endif

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
