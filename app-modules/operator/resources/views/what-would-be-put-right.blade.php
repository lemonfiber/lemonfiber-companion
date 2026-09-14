<native:column class="w-full gap-4 p-6">
    <native:text class="text-lg font-bold">{{ $this->stack()->name()->shown() }}</native:text>

    @unless ($this->isSignedIn())
        {{-- N1-R44: the session has ended, so nothing was asked. --}}
        <native:text>{{ __('connection.session_has_ended') }}</native:text>
        <native:button label="{{ __('connection.sign_in') }}" @navigate="{{ $this->goes()->signIn() }}" />
    @elseif ($this->offer()->met !== '')
        {{-- N1-R10: both sentences come off the obstacle, so this screen cannot
             describe a condition differently from the one next to it. --}}
        <native:text class="font-bold">{{ __($this->offer()->met) }}</native:text>
        <native:text>{{ __($this->offer()->remedy) }}</native:text>
    @elseif ($this->offer()->isWorking)
        {{-- N2-R7: the unconfirmed form is still a job, so this is a real state
             rather than a spinner. Said plainly, with the asking left to the
             operator — N1-R17 keeps a screen from being a poller. --}}
        <native:text class="font-bold">{{ __('health.working_it_out') }}</native:text>
        <native:text>{{ __('health.working_it_out_action') }}</native:text>
        <native:button label="{{ __('health.ask_again') }}" @tap="again()" />
    @elseif ($this->offer()->hasEnded)
        {{-- The stack has no outcome for that asking any more. Not a fault and
             not an answer: nothing was carried out, and the way forward is to
             ask again from the start. Saying so is what keeps it from reading
             as a machine that is broken. --}}
        <native:text class="font-bold">{{ __('health.nothing_came_back') }}</native:text>
        <native:text>{{ __('health.nothing_came_back_action') }}</native:text>
        <native:button label="{{ __('health.ask_again') }}" @tap="again()" />
    @elseif ($this->wasAgreedTo())
        {{-- N2-R5: what the machine actually did, which is a different question
             from what it said it would do — and rendered from a different value
             for that reason, so an offer can never appear as an outcome. --}}
        @if ($this->done()->isWorking)
            <native:text class="font-bold">{{ __('health.carrying_it_out') }}</native:text>
            <native:text>{{ __('health.carrying_it_out_action') }}</native:text>
            <native:button label="{{ __('health.ask_again') }}" @tap="again()" />
        @elseif ($this->done()->met !== '')
            <native:text class="font-bold">{{ __($this->done()->met) }}</native:text>
            <native:text>{{ __($this->done()->remedy) }}</native:text>
        @elseif ($this->done()->hasEnded)
            {{-- The one state where *it failed* is certainly the wrong word.
                 The operator does not know what happened to their machine, and
                 it may well have worked — so they are sent to look at its
                 health rather than offered the agreement again. --}}
            <native:text class="font-bold">{{ __('health.nobody_knows_what_happened') }}</native:text>
            <native:text>{{ __('health.nobody_knows_what_happened_action') }}</native:text>
        @else
            <native:text class="font-bold">
                {{ trans_choice('health.changed_count', $this->done()->changed) }}
            </native:text>

            @forelse ($this->done()->outcomes as $outcome)
                <native:column class="w-full gap-1">
                    <native:text class="font-bold">{{ $outcome->repair->does }}</native:text>
                    <native:text>{{ __($outcome->became) }}</native:text>

                    {{-- What a stopped repair left, which is the whole of what
                         tells an operator whether they may simply try again. --}}
                    @if ($outcome->left !== '')
                        <native:text class="text-sm">{{ $outcome->left }}</native:text>
                    @endif

                    <native:text class="text-sm">{{ __($outcome->repair->undoing) }}</native:text>

                    @if ($outcome->worthAnotherGo)
                        <native:text class="text-sm">{{ __('health.worth_another_go') }}</native:text>
                    @endif
                </native:column>
            @empty
                {{-- A run that finished having done nothing. Rare and real: the
                     stack took the agreement, found there was nothing left to
                     do, and said so. Told apart from a job it has forgotten,
                     which is the branch above — one means nothing needed doing
                     and the other means nobody knows. --}}
                <native:text>{{ __('health.nothing_was_carried_out') }}</native:text>
            @endforelse
        @endif
    @else
        @forelse ($this->offer()->repairs as $repair)
            <native:column class="w-full gap-1">
                {{-- N2-R4: all three clauses, in the order the requirement puts
                     them, and before anything asks for a yes. What it does, what
                     else it touches, and whether it can be taken back. --}}
                <native:text class="font-bold">{{ $repair->does }}</native:text>

                @forelse ($repair->effects as $effect)
                    <native:text class="text-sm">{{ $effect }}</native:text>
                @empty
                    <native:text class="text-sm">{{ __('health.affects_nothing_else') }}</native:text>
                @endforelse

                <native:text>{{ __($repair->undoing) }}</native:text>

                {{-- N2-R5: the yes is its own act, asked for after all three of
                     `N2-R4`'s statements have been made and not before. Named
                     by the check rather than by position, because the listing
                     is re-read every frame and a position is a fact about the
                     list rather than about the repair. --}}
                <native:button
                    label="{{ __('health.agree_to_it') }}"
                    @tap="agreeTo('{{ $repair->answers }}')"
                />
            </native:column>
        @empty
            {{-- A stack with nothing to put right is the healthy case, and it is
                 told apart from a job that ended: there is nothing to fix, which
                 is an answer, rather than ask me again. --}}
            <native:text class="font-bold">{{ __('health.nothing_to_put_right') }}</native:text>
        @endforelse

        <native:button label="{{ __('health.ask_again') }}" @tap="again()" />
    @endunless

    <native:button label="{{ __('health.back_to_the_stack') }}" @navigate="{{ $this->goes()->health() }}" />
</native:column>
