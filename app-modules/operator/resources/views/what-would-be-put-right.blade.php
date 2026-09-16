<x-operator::screen-opens :title="$this->stack()->name()->shown()" />

<native:column class="w-full gap-4 px-6 py-4">

    @unless ($this->isSignedIn())
        {{-- N1-R44: the session has ended, so nothing was asked. --}}
        <native:text>{{ __('connection.session_has_ended') }}</native:text>
        <x-operator::action label="{{ __('connection.sign_in') }}" :goes="$this->goes()->signIn()" />
    @elseif ($this->offer()->went->met !== '')
        {{-- N1-R10: both sentences come off the obstacle, so this screen cannot
             describe a condition differently from the one next to it. --}}
        <x-operator::emphasis>{{ __($this->offer()->went->met) }}</x-operator::emphasis>
        <native:text>{{ __($this->offer()->went->remedy) }}</native:text>

        {{-- N1-R3: the action stays on the screen and the failure is reported
             beside it. An obstacle branch with nothing on it leaves an operator
             whose stack woke up two seconds later with no way to find out. --}}
        <x-operator::action label="{{ __('health.ask_again') }}" tap="lookAgain()" />
    @elseif ($this->offer()->isWorking)
        {{-- N2-R7: the unconfirmed form is still a job, so this is a real state
             rather than a spinner. Said plainly, with the asking left to the
             operator — N1-R17 keeps a screen from being a poller. --}}
        <x-operator::emphasis>{{ __('health.working_it_out') }}</x-operator::emphasis>
        <native:text>{{ __('health.working_it_out_action') }}</native:text>

        {{-- The same cadence, on the other state the stack works through. --}}
        <x-operator::note>
            {{ __($this->cadence()->saidOnTheScreen(), ['count' => $this->cadence()->seconds()]) }}
        </x-operator::note>
        <x-operator::action label="{{ __('health.ask_again') }}" tap="again()" />
    @elseif ($this->offer()->hasEnded)
        {{-- The stack has no outcome for that asking any more. Not a fault and
             not an answer: nothing was carried out, and the way forward is to
             ask again from the start. Saying so is what keeps it from reading
             as a machine that is broken. --}}
        <x-operator::emphasis>{{ __('health.nothing_came_back') }}</x-operator::emphasis>
        <native:text>{{ __('health.nothing_came_back_action') }}</native:text>
        <x-operator::action label="{{ __('health.ask_again') }}" tap="again()" />
    @elseif ($this->wasAgreedTo())
        {{-- N2-R5: what the machine actually did, which is a different question
             from what it said it would do — and rendered from a different value
             for that reason, so an offer can never appear as an outcome. --}}
        @if ($this->done()->isWorking)
            <x-operator::emphasis>{{ __('health.carrying_it_out') }}</x-operator::emphasis>
            <native:text>{{ __('health.carrying_it_out_action') }}</native:text>
            {{-- N1-R27: the cadence is stated, because a screen that refreshes
                 silently is one an operator cannot reason about — they cannot
                 tell a second-old answer from a minute-old one, and whether
                 something has changed is the only reason they are looking. --}}
            <x-operator::note>
                {{ __($this->cadence()->saidOnTheScreen(), ['count' => $this->cadence()->seconds()]) }}
            </x-operator::note>
            <x-operator::action label="{{ __('health.ask_again') }}" tap="again()" />
        @elseif (! $this->done()->went->cameBack())
            <x-operator::emphasis>{{ __($this->done()->went->met) }}</x-operator::emphasis>
            <native:text>{{ __($this->done()->went->remedy) }}</native:text>

            {{-- N1-R3 again, and the sharper half of it: this obstacle stands
                 between the operator and the answer to *did it work*. Taking
                 the action away leaves them with a machine they told to change
                 something and no way to ask what happened. --}}
            <x-operator::action label="{{ __('health.ask_again') }}" tap="again()" />
        @elseif ($this->done()->hasEnded)
            {{-- The one state where *it failed* is certainly the wrong word.
                 The operator does not know what happened to their machine, and
                 it may well have worked — so they are sent to look at its
                 health rather than offered the agreement again. --}}
            <x-operator::emphasis>{{ __('health.nobody_knows_what_happened') }}</x-operator::emphasis>
            <native:text>{{ __('health.nobody_knows_what_happened_action') }}</native:text>
        @else
            <x-operator::emphasis>
                {{ trans_choice('health.changed_count', $this->done()->changed) }}
            </x-operator::emphasis>

            @forelse ($this->done()->outcomes as $outcome)
                <x-operator::entry>
                    <x-operator::emphasis>{{ $outcome->repair->does }}</x-operator::emphasis>
                    <native:text>{{ __($outcome->became) }}</native:text>

                    {{-- What a stopped repair left, which is the whole of what
                         tells an operator whether they may simply try again. --}}
                    @if ($outcome->left !== '')
                        <x-operator::note>{{ $outcome->left }}</x-operator::note>
                    @endif

                    <x-operator::note>{{ __($outcome->repair->undoing) }}</x-operator::note>

                    @if ($outcome->worthAnotherGo)
                        <x-operator::note>{{ __('health.worth_another_go') }}</x-operator::note>
                    @endif
                </x-operator::entry>
            @empty
                {{-- A run that finished having done nothing. Rare and real: the
                     stack took the agreement, found there was nothing left to
                     do, and said so. Told apart from a job it has forgotten,
                     which is the branch above — one means nothing needed doing
                     and the other means nobody knows. --}}
                <native:text>{{ __('health.nothing_was_carried_out') }}</native:text>
            @endforelse

            {{-- A listing usually holds more than one repair, and agreeing to
                 one is not agreeing to the rest. Everything is asked afresh
                 rather than the old listing kept: the machine has just changed,
                 so what it would offer now is not necessarily what it offered
                 before. --}}
            <x-operator::action label="{{ __('health.look_again') }}" tap="lookAgain()" />
        @endif
    @else
        @forelse ($this->offer()->repairs as $repair)
            <x-operator::entry>
                {{-- N2-R4: all three clauses, in the order the requirement puts
                     them, and before anything asks for a yes. What it does, what
                     else it touches, and whether it can be taken back. --}}
                <x-operator::emphasis>{{ $repair->does }}</x-operator::emphasis>

                @forelse ($repair->effects as $effect)
                    <x-operator::note>{{ $effect }}</x-operator::note>
                @empty
                    <x-operator::note>{{ __('health.affects_nothing_else') }}</x-operator::note>
                @endforelse

                <native:text>{{ __($repair->undoing) }}</native:text>

                {{-- N2-R5: the yes is its own act, asked for after all three of
                     `N2-R4`'s statements have been made and not before. Named
                     by the check rather than by position, because the listing
                     is re-read every frame and a position is a fact about the
                     list rather than about the repair. --}}
                <x-operator::action label="{{ __('health.agree_to_it') }}" tap="agreeTo('{{ $repair->answers }}')" />
            </x-operator::entry>
        @empty
            {{-- A stack with nothing to put right is the healthy case, and it is
                 told apart from a job that ended: there is nothing to fix, which
                 is an answer, rather than ask me again. --}}
            <x-operator::emphasis>{{ __('health.nothing_to_put_right') }}</x-operator::emphasis>
        @endforelse

        <x-operator::action label="{{ __('health.ask_again') }}" tap="again()" />
    @endunless

    {{-- No way back of its own. The bar under this screen carries the machine's
         four readings and *Health* is the way back to it, so a button here was
         the same destination twice on one frame — and this screen already
         offers an agreement, a retry and whatever a finished run left behind.
         `ScreensSpeakToTheOperatorTest` reads the composed screen, which is why
         removing it leaves the leaf still reachable. --}}
</native:column>

<x-operator::screen-closes :goes="$this->goes()" here="repairs" />
