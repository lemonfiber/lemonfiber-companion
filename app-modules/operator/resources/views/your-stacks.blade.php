<x-operator::screen-opens :title="__('navigation.your_stacks')" />

<x-operator::content>
    @if ($this->howItOpened()->isLocked)
        {{-- The device's own authentication on a cold start, asked
             before anything reads retained state or touches a network. Nothing
             below is drawn — not the machine names, not how they stand, not
             the diagnostics control — because all of it is what the lock is for. --}}
        <x-design::title>{{ __('device.unlock_reason') }}</x-design::title>

        {{-- A button rather than an automatic retry. An operator who
             dismissed the prompt meant it, and a screen that asked again
             immediately is what teaches people to turn a feature off. --}}
        <x-design::action label="{{ __('device.unlock') }}" tap="tryToUnlock()" />
    @else
    {{-- What stood between this launch and the machine, shown rather
         than discarded: a screen that decided "no network" and then drew the
         machine names and their last words as though nothing were wrong leaves
         somebody tapping a stack their phone cannot reach.

         Above the list and not instead of it. The words below are retained,
         which is exactly what they are for — a device with no signal is when
         the last thing a stack said is worth most — and the diagnostics control
         at the bottom is the one thing that still works when nothing else does.
         The remedy is said beside it: what happened is a fact about the world,
         and what to do about it is advice. --}}
    @if ($this->howItOpened()->met !== '')
        <x-design::notice tone="unknown">
            <x-design::strong>{{ __($this->howItOpened()->met) }}</x-design::strong>
            <x-design::body>{{ __($this->howItOpened()->remedy) }}</x-design::body>
        </x-design::notice>
    @endif

    {{-- Said once, on the first frame past the lock: the phone's key had gone,
         so what it kept could no longer be read and was cleared. The pairings
         stayed, which is the half an operator needs to hear before they worry
         about the list below. --}}
    @if ($this->savedDataWasCleared())
        <x-design::notice tone="attention">
            <x-design::body>{{ __('connection.saved_data_cleared') }}</x-design::body>
        </x-design::notice>
    @endif

    @if ($this->nothingIsPairedYet())
        {{-- A sequence rather than a wall: one step per frame, each stating
             its own position, and pairing at the end of it.

             Drawn only where nothing is paired, so a device holding a pairing
             never evaluates it, the sequence cannot be re-entered and nothing
             has to remember that it was finished. --}}
        <x-operator::first-run :at="$this->firstRunIsAt()" on="goOn()" leave="skipAhead()" />
    @else
        {{-- A row that is tapped, not a button that is pressed: a list of
             machines is not a list of calls to action. The row says what it
             is about, and a reader hears *open this machine* while the eye
             reads the machine's state.

             How the stack stands is what the app opens on: the core's one
             line, in the sentence the stack's own screen says it in, with its
             glyph at the row's start. A stack whose line was never heard says
             it cannot be told, never nothing. It is held rather than asked, so
             it carries when it was heard and is never drawn as though it were
             current; the age comes out of the same fold as the word, so a row
             cannot have one without the other. Then whether this device holds
             a session for it. --}}
        <x-design::section>
            @forelse ($this->configured() as $stack)
                <x-design::row
                    :headline="$stack->name()->shown()"
                    :supporting="__($this->lastKnownOf($stack)->said)
                        . ($this->lastKnownOf($stack)->ago->said === '' ? '' : ' ' . __('health.summary.as_of', ['ago' => trans_choice($this->lastKnownOf($stack)->ago->said, $this->lastKnownOf($stack)->ago->count)]))
                        . ' · ' . __($this->isSignedInto($stack) ? 'connection.stack_is_open' : 'connection.stack_wants_a_password')"
                    :tone="$this->lastKnownOf($stack)->tone"
                    :goes="$this->tappingGoesTo($stack)"
                    :answers-to="__('connection.open_stack', ['stack' => $stack->name()->shown()])"
                />
            @empty
                {{-- Unreachable while the branch above guards it: a device
                     with nothing paired draws the first run instead. --}}
            @endforelse
        </x-design::section>
    @endif

    @if ($this->sharingWent() !== '')
        <x-design::notice tone="unknown">
            <x-design::strong>{{ __($this->sharingWent()) }}</x-design::strong>
            <x-design::body>{{ __($this->sharingRemedy()) }}</x-design::body>
        </x-design::notice>
    @endif

    {{-- The sequence's last step, and the way to add another machine. Guarded
         rather than moved into the sequence: an operator with a stack already
         paired is on this screen to add another, and the same control answers
         both — one spelling, one set of tests.

         One road offered here and the other where it is used. An operator who
         opens the app to add a machine is choosing to add one, not choosing
         between a camera and a keyboard; the scanning screen offers the typed
         road beside the camera, which is where somebody is when the question
         is real. --}}
    @if ($this->pairingIsOffered())
        <x-design::action label="{{ __('connection.pair') }}" :goes="$this->scanningIsAt()" />
    @endif

    {{-- Assembled for the operator to send, and not sent by the app. On this
         screen because it is the one reachable from anywhere and the one that
         works when nothing else does — a stack that cannot be reached is
         exactly when somebody needs to ask for help.

         Not on a step of the first run, which is not the same as not
         reachable: the sequence ends on this screen with everything it offers,
         and that is where somebody stuck on a first run actually is. A line
         rather than a button, because it is not part of the pairing above it. --}}
    @unless ($this->theFirstRunIsStillRunning())
        <x-design::link label="{{ __('device.share_diagnostics') }}" tap="share()" />
    @endunless
    @endif
</x-operator::content>
