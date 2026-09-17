<x-operator::screen-opens :title="__('navigation.your_stacks')" />

<native:column class="w-full gap-4 px-6 py-4">
    @if ($this->howItOpened()->isLocked)
        {{-- N4-R19: the device's own authentication on a cold start, asked
             before anything reads retained state or touches a network. Nothing
             below is drawn — not the machine names, not a verdict, not the
             diagnostics control — because all of it is what the lock is for. --}}
        <x-operator::heading>{{ __('device.unlock_reason') }}</x-operator::heading>

        {{-- N4-R4: a button rather than an automatic retry. An operator who
             dismissed the prompt meant it, and a screen that asked again
             immediately is what teaches people to turn a feature off. --}}
        <x-operator::action label="{{ __('device.unlock') }}" tap="tryToUnlock()" />
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
        {{-- A row that is tapped, not a button that is pressed.

             The platform paints every button the same fill and honours no
             per-instance colour, so a machine drawn as a button is a full-width
             filled bar — and a household with three of them opens the app on
             three identical bars with the pairing controls under them, none of
             which reads as more or less than any other. `DES-R15` is why there
             is no second button style to reach for: hierarchy on this device is
             how many controls are on the frame, and a list of machines is not a
             list of calls to action.

             So the whole row is the tap target and carries what it is about.
             `F5` is answered by `a11y-label` rather than by a visible label,
             because what a reader should hear is *open this machine* and what
             the eye should read is the machine's state. The dimming under a
             finger is what says it is tappable at all, in place of the fill
             that used to say it. --}}
        <native:pressable
            class="w-full min-h-12 justify-center gap-1 py-2"
            @navigate="$this->tappingGoesTo($stack)"
            a11y-label="{{ __('connection.open_stack', ['stack' => $stack->name()->shown()]) }}"
            :press-opacity="0.6"
        >
            <x-operator::emphasis>{{ $stack->name()->shown() }}</x-operator::emphasis>
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
                {{-- Said plainly, with the machine's name carrying the row's one
                     emphasis. Two strong lines in a row is a row with no first
                     line, and what tells three machines apart at a glance is
                     which one this is. --}}
                <native:text>{{ __($this->lastKnownOf($stack)->said) }}</native:text>
                <x-operator::note>
                    {{ __('health.stale', [
                        'ago' => trans_choice($this->lastKnownOf($stack)->agoSaid, $this->lastKnownOf($stack)->agoCount),
                    ]) }}
                </x-operator::note>
            @endif

            <x-operator::note>
                {{ __($this->isSignedInto($stack) ? 'connection.stack_is_open' : 'connection.stack_wants_a_password') }}
            </x-operator::note>
        </native:pressable>

        {{-- Between machines rather than under each, so the last row does not
             end on a line that separates it from nothing. `F6`'s empty state is
             the arm below; this is what keeps three rows from reading as one
             paragraph of nine lines. --}}
        @unless ($loop->last)
            <native:divider />
        @endunless
    @empty
        {{-- N1-R54: a sequence rather than a wall — one frame carrying a
             heading, two sentences and three buttons at once says nothing about
             which of them to read first. One step per frame, each stating its
             own position, and pairing at the end of it.

             Inside the empty arm and not beside it, which is `N1-R56` by
             construction: a device holding a pairing never evaluates this, so
             the sequence cannot be re-entered and nothing has to remember that
             it was finished. --}}
        <x-operator::first-run :at="$this->firstRunIsAt()" on="goOn()" leave="skipAhead()" />
    @endforelse

    @if ($this->sharingWent() !== '')
        <x-operator::emphasis>{{ __($this->sharingWent()) }}</x-operator::emphasis>
        <native:text>{{ __($this->sharingRemedy()) }}</native:text>
    @endif

    {{-- N1-R6's two roads, and N1-R54's last step. Guarded rather than moved
         into the sequence: an operator with a stack already paired is on this
         screen to add another, and the same two controls answer both — one
         spelling, one set of tests. --}}
    @if ($this->pairingIsOffered())
        {{-- One road offered here and the other offered where it is used.
             `N1-R6` asks for both and does not ask for both on this screen: an
             operator who opens the app to add a machine is choosing to add one,
             not choosing between a camera and a keyboard, and two controls of
             equal weight side by side is this screen asking them to. The
             scanning screen offers the typed road beside the camera, which is
             where somebody is when the question is real. --}}
        <x-operator::action label="{{ __('connection.pair') }}" :goes="$this->scanningIsAt()" />
    @endif

    {{-- N4-R13: assembled for the operator to send, and not sent by the app.
         On this screen because it is the one reachable from anywhere and the
         one that works when nothing else does — a stack that cannot be reached
         is exactly when somebody needs to ask for help.

         Not on a step of the first run, which is not the same as not reachable:
         the sequence ends on this screen with everything it offers, and that is
         where somebody who is stuck on a first run actually is. The platform
         paints every button the same fill and the design rules refuse a second
         style — `DES-R15` measures lemon as text at 1.6:1 — so on this device
         a frame's hierarchy is how many controls are on it, and three of equal
         weight under `Step 1 of 3` says none of them is the way forward. --}}
    @unless ($this->theFirstRunIsStillRunning())
        {{-- A rule above it rather than a quieter button beside it. The
             platform paints every button the same fill and honours no
             per-instance colour, so grouping is the only hierarchy available
             here that is not an override `DES-R25` refuses — and what this
             needs to say is not *press me less*, it is *this one is not part
             of the pairing above*. --}}
        <native:divider />
        <x-operator::quiet-action label="{{ __('device.share_diagnostics') }}" tap="share()" />
    @endunless
    @endif
</native:column>
