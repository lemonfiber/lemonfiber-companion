<x-wayfinding::stack-opens :title="$this->stack()->name()->shown()" :stacks="$this->stacksToChooseFrom()" :choosing="$this->choosingAStack" />

<x-operator::content>

    @unless ($this->isSignedIn())
        {{-- The session has ended, so nothing was asked. --}}
        <x-design::body>{{ __('connection.session_has_ended') }}</x-design::body>
        <x-design::action label="{{ __('connection.sign_in') }}" :goes="$this->goes()->signIn()" />
    @elseif ($this->offer()->went->met !== '')
        {{-- Both sentences come off the obstacle, so this screen cannot
             describe a condition differently from the one next to it. --}}
        <x-design::notice tone="unknown">
            <x-design::strong>{{ __($this->offer()->went->met, $this->offer()->went->filling()) }}</x-design::strong>
            <x-design::body>{{ __($this->offer()->went->remedy, $this->offer()->went->filling()) }}</x-design::body>
        </x-design::notice>

        {{-- The action stays on the screen and the failure is reported
             beside it. An obstacle branch with nothing on it leaves an operator
             whose stack woke up two seconds later with no way to find out. --}}
        <x-design::action label="{{ __('health.ask_again') }}" tap="lookAgain()" />
        <x-operator::try-again :went="$this->offer()->went" tap="tryAgain()" />
        @if ($this->offer()->went->isPutRightInTheAppsSettings())
            <x-design::action label="{{ __('connection.open_settings') }}" tap="openTheAppsSettings()" />
            @if ($this->theSettingsWouldNotOpen)
                <x-design::note>{{ __('connection.settings_would_not_open') }}</x-design::note>
            @endif
        @endif
    @elseif ($this->offer()->isWorking)
        {{-- The unconfirmed form is still a job, so this is a real state
             rather than a spinner. Said plainly, with the asking left to the
             operator, so the screen is not a poller. --}}
        <x-design::standing
            :said="__('health.working_it_out')"
            tone="working"
        />
        <x-design::body>{{ __('health.working_it_out_action') }}</x-design::body>
        <x-design::action label="{{ __('health.ask_again') }}" tap="again()" tone="tonal" />
    @elseif ($this->offer()->hasEnded)
        {{-- The stack has no outcome for that asking any more. Not a fault and
             not an answer: nothing was carried out, and the way forward is to
             ask again from the start. Saying so is what keeps it from reading
             as a machine that is broken. --}}
        <x-design::notice tone="unknown">
            <x-design::strong>{{ __('health.nothing_came_back') }}</x-design::strong>
            <x-design::body>{{ __('health.nothing_came_back_action') }}</x-design::body>
        </x-design::notice>
        <x-design::action label="{{ __('health.ask_again') }}" tap="again()" />
    @elseif ($this->wasAgreedTo())
        {{-- What the machine actually did, which is a different question
             from what it said it would do — and rendered from a different value
             for that reason, so an offer can never appear as an outcome. --}}
        @if ($this->done()->isWorking)
            {{-- Read again on the screen's cadence until the stack says it
                 has finished. --}}
            <x-design::standing
                :said="__('health.carrying_it_out')"
                tone="working"
            />
            <x-design::body>{{ __('health.carrying_it_out_action') }}</x-design::body>
            <x-design::action label="{{ __('health.ask_again') }}" tap="again()" tone="tonal" />
        @elseif (! $this->done()->went->cameBack())
            <x-design::notice tone="unknown">
                <x-design::strong>{{ __($this->done()->went->met, $this->done()->went->filling()) }}</x-design::strong>
                <x-design::body>{{ __($this->done()->went->remedy, $this->done()->went->filling()) }}</x-design::body>
            </x-design::notice>

            {{-- The sharper half of it: this obstacle stands
                 between the operator and the answer to *did it work*. Taking
                 the action away leaves them with a machine they told to change
                 something and no way to ask what happened. --}}
            <x-design::action label="{{ __('health.ask_again') }}" tap="again()" />
            @if ($this->done()->went->isPutRightInTheAppsSettings())
                <x-design::action label="{{ __('connection.open_settings') }}" tap="openTheAppsSettings()" />
                @if ($this->theSettingsWouldNotOpen)
                    <x-design::note>{{ __('connection.settings_would_not_open') }}</x-design::note>
                @endif
            @endif
        @elseif ($this->done()->hasEnded)
            {{-- The one state where *it failed* is certainly the wrong word.
                 The operator does not know what happened to their machine, and
                 it may well have worked — so they are sent to look at its
                 health rather than offered the agreement again. --}}
            <x-design::notice tone="unknown">
                <x-design::strong>{{ __('health.nobody_knows_what_happened') }}</x-design::strong>
                <x-design::body>{{ __('health.nobody_knows_what_happened_action') }}</x-design::body>
            </x-design::notice>
        @else
            <x-design::heading>{{ trans_choice('health.changed_count', $this->done()->changed) }}</x-design::heading>

            @forelse ($this->done()->outcomes as $outcome)
                <x-design::card>
                    <x-design::strong>{{ $outcome->repair->does }}</x-design::strong>
                    <x-design::body>{{ __($outcome->became) }}</x-design::body>

                    {{-- What a stopped repair left, which is the whole of what
                         tells an operator whether they may simply try again. --}}
                    @if ($outcome->left !== '')
                        <x-design::note>{{ $outcome->left }}</x-design::note>
                    @endif

                    <x-design::note>{{ __($outcome->repair->undoing) }}</x-design::note>

                    @if ($outcome->worthAnotherGo)
                        <x-design::note>{{ __('health.worth_another_go') }}</x-design::note>
                    @endif
                </x-design::card>
            @empty
                {{-- A run that finished having done nothing. Rare and real: the
                     stack took the agreement, found there was nothing left to
                     do, and said so. Told apart from a job it has forgotten,
                     which is the branch above — one means nothing needed doing
                     and the other means nobody knows. --}}
                <x-design::body>{{ __('health.nothing_was_carried_out') }}</x-design::body>
            @endforelse

            {{-- A listing usually holds more than one repair, and agreeing to
                 one is not agreeing to the rest. Everything is asked afresh
                 rather than the old listing kept: the machine has just changed,
                 so what it would offer now is not necessarily what it offered
                 before. --}}
            <x-design::action label="{{ __('health.look_again') }}" tap="lookAgain()" tone="tonal" />
        @endif
    @else
        {{-- The last yes was refused because the offer moved: what the stack
             said comes first, then the offer as it stands now. --}}
        @if ($this->movedOn !== null)
            <x-design::card>
                <x-operator::refused-in-its-words :refused="$this->movedOn" />
            </x-design::card>
        @endif

        @forelse ($this->offer()->repairs as $repair)
            <x-design::card>
                {{-- All three clauses, in this order, and before anything asks
                     for a yes: what it does, what else it touches, and whether
                     it can be taken back. --}}
                <x-design::strong>{{ $repair->does }}</x-design::strong>

                @forelse ($repair->effects as $effect)
                    <x-design::note>{{ $effect }}</x-design::note>
                @empty
                    <x-design::note>{{ __('health.affects_nothing_else') }}</x-design::note>
                @endforelse

                <x-design::body>{{ __($repair->undoing) }}</x-design::body>

                {{-- The yes is its own act, asked for after all three
                     statements have been made and not before. Named by the
                     check rather than by position, because the listing is
                     re-read every frame and a position is a fact about the
                     list rather than about the repair. --}}
                <x-design::action
                    label="{{ __('health.agree_to_it') }}"
                    answers-to="{{ __('health.agree_to_that', ['repair' => $repair->does]) }}"
                    tap="agreeTo('{{ $repair->answers }}')"
                />
            </x-design::card>
        @empty
            {{-- An empty listing says the stack has no repair to offer, which
                 is not the same as nothing being wrong: a service that fell
                 over for a reason no repair covers leaves it empty too. Said as
                 an answer rather than as a job that ended, and with no glyph
                 that would read as all being well. --}}
            <x-design::strong>{{ __('health.nothing_to_put_right') }}</x-design::strong>
        @endforelse

        {{-- Tonal, under the yeses. Every repair above is a commitment and this
             is not one of them: a filled bar that re-reads the machine is the
             control an operator taps when they meant the one above it. --}}
        <x-design::action label="{{ __('health.ask_again') }}" tap="again()" tone="tonal" />
    @endunless

    {{-- No way back of its own. The bar under this screen carries the machine's
         four readings and *Health* is the way back to it, so a button here was
         the same destination twice on one frame — and this screen already
         offers an agreement, a retry and whatever a finished run left behind.
         `ScreensSpeakToTheOperatorTest` reads the composed screen, which is why
         removing it leaves the leaf still reachable. --}}
</x-operator::content>

<x-operator::screen-closes :goes="$this->goes()" :here="$this->itsTab()" :marks="$this->marks()" />
