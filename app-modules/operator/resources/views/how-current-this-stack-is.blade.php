<x-operator::screen-opens :title="$this->stack()->name()->shown()" />

<x-operator::what-stopped-the-reading
    :signed-in="$this->answer()->isSignedIn"
    :met="$this->answer()->met"
    :remedy="$this->answer()->remedy"
    :sign-in-goes-to="$this->goes()->signIn()"
/>

@if ($this->answer()->isSignedIn && $this->answer()->met === '')
<native:column class="w-full gap-4 px-6 py-4">
@if ($this->asking() !== null)
    {{-- N2-R17: asked before it runs, and the question names the services
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

    <x-operator::action label="{{ __('health.go_ahead') }}" tap="agree()" />
    <x-operator::action label="{{ __('health.never_mind') }}" tap="neverMind()" />
@else
    {{-- N2-R15: the answer, first. Current, waiting, or not looked at
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
        {{-- N2-R16: a stack running a release that has since been taken
             back is something the operator has to be told. Left out of what
             is offered below, and said here — dropping it from both would
             leave them reading a screen that says nothing is wrong. --}}
        <x-operator::emphasis>{{ __('updates.running_withdrawn') }}</x-operator::emphasis>
    @endif

    @if ($this->answer()->anyWorthNoticing)
        {{-- N2-R16: said before the list, because it is what makes tonight
             a decision rather than a chore. An operator who reads *the
             household will see the difference* before the versions is
             deciding on the evening rather than on a number. --}}
        <x-operator::emphasis>{{ __('updates.something_worth_noticing') }}</x-operator::emphasis>
    @endif

    @forelse ($this->answer()->waiting as $release)
        <x-operator::entry>
            <x-operator::emphasis>{{ $release->version }}</x-operator::emphasis>

            @if ($this->answer()->canTakeOne)
                {{-- N2-R20: offered only where the stack reported an update
                     waiting. A screen that counted rows would offer one to a
                     stack that listed releases while calling itself current,
                     which is the case this requirement exists for. --}}
                <x-operator::action label="{{ __('updates.take_this_one') }}" tap="wouldYouLike('{{ $release->version }}')" />
            @endif

            {{-- N2-R16: whether somebody in the house would see the
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
        {{-- N2-R20: nothing is offered where the stack reported none
             waiting, and the empty state says so rather than leaving a
             blank where a list belongs. --}}
        <native:text>{{ __('updates.nothing_waiting') }}</native:text>
    @endforelse

    {{-- N2-R18: what became of the last update, per service. Below what is
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

            {{-- N2-R18: which of the four, on the row. Flattened into
                 *failed*, the three ways of not arriving send an operator
                 to look in the wrong place. --}}
            <x-operator::note>{{ __($took->endingSaid) }}</x-operator::note>

            @unless ($took->arrived)
                {{-- N2-R19: which way back, named. A rollback and a restore
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

    {{-- The way back, which every screen under a machine offers. A leaf a
         person can enter and not leave is a dead end on a handset: the
         platform's own gesture may be there, and a screen that counts on it
         is a screen that works on one handset and traps somebody on
         another. --}}
    <x-operator::action label="{{ __('health.back_to_the_stack') }}" :goes="$this->goes()->health()" />
@endif
</native:column>
@endif

<x-operator::screen-closes :goes="$this->goes()" here="updates" />
