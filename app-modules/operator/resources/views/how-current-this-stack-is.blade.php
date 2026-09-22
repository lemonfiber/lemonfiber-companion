<x-operator::screen-opens :title="$this->stack()->name()->shown()" />

@if ($this->answer()->went->cameBack())
<native:column class="w-full gap-4 px-6 py-4">
@if ($this->asking() !== null)
    {{-- Asked before it runs, and the question names the services
         it would change. A screen of its own rather than a line beside the
         row, because a confirmation an operator can tap past without
         reading is the same as no confirmation.

         Every update asks. There is no unconfirmed arm here the way there
         is for a start: there is no update that takes nothing away, and
         which services it takes away is the whole of what this says. --}}
    <x-operator::heading>
        {{ __('updates.about_to_take', ['version' => $this->asking()->release()->version()]) }}
    </x-operator::heading>

    @if ($this->asking()->changesNothing())
        {{-- A release that changes no service is a changelog entry rather
             than an evening, and saying so is better than asking somebody
             to confirm nothing. --}}
        <native:text>{{ __('updates.changes_nothing') }}</native:text>
    @else
        {{-- The count first, because it is the size of the evening, and
             then the names — an operator deciding at eleven at night reads
             *four services* before they read which four. --}}
        <native:text>{{ trans_choice('updates.would_change', $this->wouldChange()) }}</native:text>

        @forelse ($this->asking()->changing() as $service)
            <x-operator::note>{{ $service->named() }}</x-operator::note>
        @empty
            {{-- Unreachable while the branch above guards it, and written
                 anyway: `F6` wants the empty case to be the same edit as
                 the loop, so removing the guard cannot silently turn
                 *it changes nothing* into a blank space. --}}
            <x-operator::note>{{ __('updates.changes_nothing') }}</x-operator::note>
        @endforelse
    @endif

    @if ($this->asking()->cannotBeWhollyUndone())
        {{-- Between the list and the buttons, which is where it has to be:
             an operator who has read what moves tonight and not yet agreed.
             Said against the services it is true of rather than over the
             whole evening — an update can move four services and be undoable
             for three of them, and a warning that covered all four would be
             refused as easily as it would be believed. --}}
        <x-operator::emphasis>
            {{ trans_choice('updates.cannot_be_put_back', $this->cannotBePutBack()) }}
        </x-operator::emphasis>

        @forelse ($this->asking()->cannotBePutBack() as $service)
            <x-operator::note>{{ $service->named() }}</x-operator::note>
        @empty
            {{-- Unreachable while the branch above guards it, and written
                 anyway for the reason the list above writes its own: removing
                 the guard must not turn a named warning into an unnamed one,
                 which is the shape that warns about nothing in particular. --}}
            <x-operator::note>{{ __('updates.cannot_be_put_back_after') }}</x-operator::note>
        @endforelse

        <native:text>{{ __('updates.cannot_be_put_back_after') }}</native:text>
    @endif

    <x-operator::action label="{{ __('health.go_ahead') }}" tap="agree()" />
    <x-operator::action label="{{ __('health.never_mind') }}" tap="neverMind()" />
@else
    {{-- The answer, first. Current, waiting, or not looked at
         recently — the stack's own word, not one worked out here from two
         version strings. --}}
    <x-operator::emphasis>{{ __($this->answer()->howSaid) }}</x-operator::emphasis>

    {{-- What it is on. Rendered in every state rather than only where
         something is waiting: a screen silent about the version teaches an
         operator to read silence, and silence is also what a screen that
         forgot the field produces. --}}
    <x-operator::note>
        {{ __('updates.running_on', ['version' => $this->answer()->running]) }}
    </x-operator::note>

    @if ($this->answer()->runningWasWithdrawn)
        {{-- A stack running a release that has since been taken
             back is something the operator has to be told. Left out of what
             is offered below, and said here — dropping it from both would
             leave them reading a screen that says nothing is wrong. --}}
        <x-operator::emphasis>{{ __('updates.running_withdrawn') }}</x-operator::emphasis>
    @endif

    @if ($this->answer()->anyWorthNoticing)
        {{-- Said before the list, because it is what makes tonight
             a decision rather than a chore. An operator who reads *the
             household will see the difference* before the versions is
             deciding on the evening rather than on a number. --}}
        <x-operator::emphasis>{{ __('updates.something_worth_noticing') }}</x-operator::emphasis>
    @endif

    @forelse ($this->answer()->waiting as $release)
        <x-operator::entry>
            <x-operator::emphasis>{{ $release->version }}</x-operator::emphasis>

            @if ($this->answer()->canTakeOne)
                {{-- Offered only where the stack reported an update
                     waiting. A screen that counted rows would offer one to a
                     stack that listed releases while calling itself current,
                     which is the case this requirement exists for. --}}
                <x-operator::action
                    label="{{ __('updates.take_this_one') }}"
                    answers-to="{{ __('updates.take_that_one', ['version' => $release->version]) }}"
                    tap="wouldYouLike('{{ $release->version }}')"
                />
            @endif

            {{-- What taking it would change, in the stack's own words, where
                 the operator is deciding. A version string is an identifier
                 and not an argument, and this is the row the *take this one*
                 control sits on — so the sentence belongs here rather than
                 behind it. Said as the stack said it: this app composes
                 nothing, and a release the stack had nothing to say about says
                 so rather than drawing a blank. --}}
            @if ($release->saysWhatItDelivers)
                <native:text>{{ $release->deliversSaid }}</native:text>
            @else
                <x-operator::note>{{ __('updates.delivers_unsaid') }}</x-operator::note>
            @endif

            {{-- Whether somebody in the house would see the
                 difference. This is what makes the update a decision rather
                 than a chore, so it is on the row and not in a footnote. --}}
            <x-operator::note>
                @if ($release->theHouseholdWouldNotice)
                    {{ __('updates.would_be_noticed') }}
                @else
                    {{ __('updates.would_not_be_noticed') }}
                @endif
            </x-operator::note>
        </x-operator::entry>
    @empty
        {{-- Nothing is offered where the stack reported none
             waiting, and the empty state says so rather than leaving a
             blank where a list belongs. --}}
        <native:text>{{ __('updates.nothing_waiting') }}</native:text>
    @endforelse

    {{-- What became of the last update, per service. Below what is
         waiting because it is the older question, and on the same screen
         because an operator deciding whether tonight is the night needs to
         know that last night went half way. --}}
    <x-operator::emphasis>{{ __('updates.last_update') }}</x-operator::emphasis>

    @if ($this->answer()->didNotArrive > 0)
        <x-operator::emphasis>
            {{ trans_choice('updates.did_not_arrive', $this->answer()->didNotArrive) }}
        </x-operator::emphasis>
    @endif

    @if ($this->answer()->anythingUnanswered)
        {{-- Apart from the line above, and not a louder version of it. A
             service that would not come back up is something to fix; one
             that started and never answered is something the stack does
             not know about, and sends somebody to a different place. --}}
        <native:text>{{ __('updates.unanswered') }}</native:text>
    @endif

    @forelse ($this->answer()->applied as $took)
        <x-operator::entry>
            <x-operator::emphasis>{{ $took->service }}</x-operator::emphasis>

            {{-- Which of the four, on the row. Flattened into
                 *failed*, the three ways of not arriving send an operator
                 to look in the wrong place. --}}
            <x-operator::note>{{ __($took->endingSaid) }}</x-operator::note>

            @unless ($took->arrived)
                {{-- Which way back, named. A rollback and a restore
                     are not one offer, and the app says the one the stack
                     named rather than the word they have in common. --}}
                <x-operator::note>{{ __($took->undoSaid) }}</x-operator::note>

                @if ($took->undoCarriesTheDataWithIt)
                    {{-- The difference worth knowing before agreeing: a
                         restore undoes more than the update did. --}}
                    <x-operator::note>{{ __('updates.undo_carries_data') }}</x-operator::note>
                @endif
            @endunless
        </x-operator::entry>
    @empty
        <native:text>{{ __('updates.nothing_applied') }}</native:text>
    @endforelse

    {{-- A screen an operator cannot ask again is a screen that relies
         on being left and returned to, which is the one thing the requirement
         names. It sat on the obstacle arm only — so a reading that failed could
         be retried and a reading that came back could not, which is the wrong
         way round: somebody watching a stuck download or an update land is
         looking at a screen they want to ask again.

         Last, under what it is about, for the health screen's reason: somebody
         who has just changed something scrolls to the end of what they were
         reading, and that is where they want to ask whether it took. --}}
    <x-operator::action label="{{ __('health.ask_again') }}" tap="again()" />
@endif
</native:column>
@else
    <x-operator::what-stopped-the-reading
        :went="$this->answer()->went"
        :sign-in-goes-to="$this->goes()->signIn()"
    />
@endif

<x-operator::screen-closes :goes="$this->goes()" here="updates" />
