<x-operator::screen-opens :title="$this->stack()->name()->shown()" />

@if ($this->answer()->went->cameBack())
<x-operator::content>
@if ($this->asking() !== null)
    {{-- Asked before it runs, and the question names the services
         it would change. A screen of its own rather than a line beside the
         row, because a confirmation an operator can tap past without
         reading is the same as no confirmation.

         Every update asks. There is no unconfirmed arm here the way there
         is for a start: there is no update that takes nothing away, and
         which services it takes away is the whole of what this says. --}}
    <x-operator::heading>{{ __('updates.about_to_take') }}</x-operator::heading>

    {{-- The count first, because it is the size of the evening, and then the
         names — an operator deciding at eleven at night reads *four services*
         before they read which four. --}}
    <native:text>{{ trans_choice('updates.would_change', $this->wouldChange()) }}</native:text>

    @forelse ($this->asking()->changing() as $service)
        <x-operator::note>{{ $service->named() }}</x-operator::note>
    @empty
        {{-- Unreachable while an offer needs a service that would move, and
             written anyway: `F6` wants the empty case to be the same edit as
             the loop, so a change to that rule cannot silently turn *it
             changes nothing* into a blank space. --}}
        <x-operator::note>{{ __('updates.changes_nothing') }}</x-operator::note>
    @endforelse

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
    {{-- The answer, first: whether any service would move onto the version
         its pins name — the stack's own word, not one worked out here from
         two version strings. --}}
    <x-operator::emphasis>{{ __($this->answer()->pinsSaid) }}</x-operator::emphasis>

    {{-- What it is on. Rendered in every state rather than only where
         something is waiting: a screen silent about the version teaches an
         operator to read silence, and silence is also what a screen that
         forgot the field produces. --}}
    <x-operator::note>
        {{ __('updates.running_on', ['version' => $this->answer()->running]) }}
    </x-operator::note>

    @if ($this->answer()->runningWasWithdrawn)
        {{-- A stack running a release that has since been taken back is
             something the operator has to be told, and the reason no update
             is offered onto the pins that release carries. --}}
        <x-operator::emphasis>{{ __('updates.running_withdrawn') }}</x-operator::emphasis>
    @endif

    @if ($this->answer()->offer !== null)
        {{-- One offer, for the stack: it moves each service onto the version
             its build pins, and the confirmation names which services. --}}
        <native:text>{{ trans_choice('updates.would_change', $this->answer()->offer->changing()->count()) }}</native:text>
        <x-operator::action label="{{ __('updates.take_it') }}" tap="wouldYouLike()" />
    @endif

    @if ($this->answer()->inUse !== null)
        {{-- What the release in use changed. Its build carries the pins, so
             this is the reason an update would move anything — and whether the
             household will notice is what makes it a decision. --}}
        <x-operator::emphasis>
            {{ __('updates.what_it_changed', ['version' => $this->answer()->inUse->version]) }}
        </x-operator::emphasis>

        @if ($this->answer()->inUse->deliversSaid !== null)
            <native:text>{{ $this->answer()->inUse->deliversSaid }}</native:text>
        @else
            <x-operator::note>{{ __('updates.delivers_unsaid') }}</x-operator::note>
        @endif

        <x-operator::note>
            @if ($this->answer()->inUse->theHouseholdWouldNotice)
                {{ __('updates.would_be_noticed') }}
            @else
                {{ __('updates.would_not_be_noticed') }}
            @endif
        </x-operator::note>
    @endif

    {{-- Every release the stack's record holds, newest first. History up to
         the release running, drawn as history: nothing here is offered, and
         nothing is taken from this list. --}}
    <x-operator::emphasis>{{ __('updates.history') }}</x-operator::emphasis>

    @forelse ($this->answer()->history as $release)
        <x-operator::entry>
            <x-operator::emphasis>{{ $release->version }}</x-operator::emphasis>

            @if ($release->wasWithdrawn)
                <x-operator::note>{{ __('updates.withdrawn') }}</x-operator::note>
            @endif

            {{-- Said as the stack said it: this app composes nothing, and a
                 release the stack had nothing to say about says so rather than
                 drawing a blank. --}}
            @if ($release->deliversSaid !== null)
                <native:text>{{ $release->deliversSaid }}</native:text>
            @else
                <x-operator::note>{{ __('updates.delivers_unsaid') }}</x-operator::note>
            @endif

            <x-operator::note>
                @if ($release->theHouseholdWouldNotice)
                    {{ __('updates.would_be_noticed') }}
                @else
                    {{ __('updates.would_not_be_noticed') }}
                @endif
            </x-operator::note>
        </x-operator::entry>
    @empty
        <native:text>{{ __('updates.no_history') }}</native:text>
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
</x-operator::content>
@else
    <x-operator::what-stopped-the-reading
        :went="$this->answer()->went"
        :sign-in-goes-to="$this->goes()->signIn()"
    />
@endif

<x-operator::screen-closes :goes="$this->goes()" here="updates" />
