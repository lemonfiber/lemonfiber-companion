<x-operator::screen
    :title="__('navigation.your_stacks')"
>
    @if ($this->howItOpened()->isLocked)
        {{-- N4-R19: the device's own authentication on a cold start, asked
             before anything reads retained state or touches a network. Nothing
             below is drawn — not the machine names, not a verdict, not the
             diagnostics control — because all of it is what the lock is for. --}}
        <x-operator::heading>{{ __('device.unlock_reason') }}</x-operator::heading>

        {{-- N4-R4: a button rather than an automatic retry. An operator who
             dismissed the prompt meant it, and a screen that asked again
             immediately is what teaches people to turn a feature off. --}}
        <native:button label="{{ __('device.unlock') }}" @tap="tryToUnlock()" />
    @else
    {{-- N1-R37: what stood between this launch and the machine, shown rather
         than discarded. Producing the answer is half of the requirement; a
         screen that decided "no network" and then drew the machine names and a
         stale verdict as though nothing were wrong leaves somebody tapping a
         stack their phone cannot reach.

         Above the list and not instead of it. The verdicts below are retained,
         which is exactly what they are for — a device with no signal is when
         the last thing a stack said is worth most — and the diagnostics control
         at the bottom is the one thing that still works when nothing else does.
         N1-R10 wants the remedy too: what happened is a fact about the world,
         and what to do about it is advice. --}}
    @if ($this->howItOpened()->met !== '')
        <x-operator::heading>{{ __($this->howItOpened()->met) }}</x-operator::heading>
        <native:text>{{ __($this->howItOpened()->remedy) }}</native:text>
    @endif

    @forelse ($this->configured() as $stack)
        <x-operator::entry>
            <native:button
                label="{{ $stack->name()->shown() }}"
                @navigate="$this->tappingGoesTo($stack)"
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
                <x-operator::emphasis>{{ __($this->lastKnownOf($stack)->said) }}</x-operator::emphasis>
                <x-operator::note>
                    {{ __('health.stale', [
                        'ago' => trans_choice($this->lastKnownOf($stack)->agoSaid, $this->lastKnownOf($stack)->agoCount),
                    ]) }}
                </x-operator::note>
            @endif

            <native:text>
                {{ __($this->isSignedInto($stack) ? 'connection.stack_is_open' : 'connection.stack_wants_a_password') }}
            </native:text>
        </x-operator::entry>
    @empty
        <x-operator::heading>{{ __('connection.no_stacks') }}</x-operator::heading>
        <native:text>{{ __('connection.setup_is_at_the_machine') }}</native:text>
        <native:text>{{ __('connection.no_stacks_action') }}</native:text>
    @endforelse

    @if ($this->sharingWent() !== '')
        <x-operator::emphasis>{{ __($this->sharingWent()) }}</x-operator::emphasis>
        <native:text>{{ __($this->sharingRemedy()) }}</native:text>
    @endif

    <native:button label="{{ __('connection.pair') }}" @navigate="$this->scanningIsAt()" />
    <native:button label="{{ __('connection.pair_by_typing') }}" @navigate="$this->typingIsAt()" />

    {{-- N4-R13: assembled for the operator to send, and not sent by the app.
         On this screen because it is the one reachable from anywhere and the
         one that works when nothing else does — a stack that cannot be reached
         is exactly when somebody needs to ask for help. --}}
    <native:button label="{{ __('device.share_diagnostics') }}" @tap="share()" />
    @endif
</x-operator::screen>
