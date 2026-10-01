<x-operator::stack-opens :title="$this->stack()->name()->shown()" :stacks="$this->stacksToChooseFrom()" :choosing="$this->choosingAStack" />

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
    <x-design::title>{{ __('updates.about_to_take') }}</x-design::title>

    {{-- The count first, because it is the size of the evening, and then the
         names — an operator deciding at eleven at night reads *four services*
         before they read which four. --}}
    <x-design::body>{{ trans_choice('updates.would_change', $this->wouldChange()) }}</x-design::body>

    <x-design::section>
        @forelse ($this->asking()->changing() as $service)
            <x-design::row :headline="$service->named()" />
        @empty
            {{-- Unreachable while an offer needs a service that would move,
                 and written anyway: `F6` wants the empty case to be the same
                 edit as the loop, so a change to that rule cannot silently
                 turn *it changes nothing* into a blank space. --}}
            <x-design::row :headline="__('updates.changes_nothing')" />
        @endforelse
    </x-design::section>

    @if ($this->asking()->cannotBeWhollyUndone())
        {{-- Between the list and the buttons, which is where it has to be:
             an operator who has read what moves tonight and not yet agreed.
             Said against the services it is true of rather than over the
             whole evening — an update can move four services and be undoable
             for three of them, and a warning that covered all four would be
             refused as easily as it would be believed. --}}
        <x-design::notice>
            <x-design::strong>{{ trans_choice('updates.cannot_be_put_back', $this->cannotBePutBack()) }}</x-design::strong>

            @forelse ($this->asking()->cannotBePutBack() as $service)
                <x-design::body>{{ $service->named() }}</x-design::body>
            @empty
                {{-- Unreachable while the branch above guards it, and written
                     anyway for the reason the list above writes its own:
                     removing the guard must not turn a named warning into an
                     unnamed one, which is the shape that warns about nothing
                     in particular. --}}
                <x-design::body>{{ __('updates.cannot_be_put_back_after') }}</x-design::body>
            @endforelse

            <x-design::note>{{ __('updates.cannot_be_put_back_after') }}</x-design::note>
        </x-design::notice>
    @endif

    <x-design::action label="{{ __('health.go_ahead') }}" tap="agree()" />
    <x-design::action label="{{ __('health.never_mind') }}" tap="neverMind()" tone="tonal" />
@else
    {{-- The answer, first: whether any service would move onto the version
         its pins name — the stack's own word, not one worked out here from
         two version strings. Where one would, the answer is how many are
         behind, and the version it is on is the one that has newer versions
         of them: its build carries the pins.

         The version is rendered in every state rather than only where
         something is waiting: a screen silent about the version teaches an
         operator to read silence, and silence is also what a screen that
         forgot the field produces. --}}
    @if ($this->answer()->offer !== null)
        <x-design::heading>{{ trans_choice('updates.behind', $this->answer()->offer->changing()->count()) }}</x-design::heading>
        <x-design::note>{{ __('updates.newer_in', ['version' => $this->answer()->running]) }}</x-design::note>
    @else
        <x-design::heading>{{ __($this->answer()->pinsSaid) }}</x-design::heading>
        <x-design::note>{{ __('updates.running_on', ['version' => $this->answer()->running]) }}</x-design::note>
    @endif

    @if ($this->answer()->runningWasWithdrawn)
        {{-- A stack running a release that has since been taken back is
             something the operator has to be told, and the reason no update
             is offered onto the pins that release carries. --}}
        <x-design::notice>
            <x-design::strong>{{ __('updates.running_withdrawn') }}</x-design::strong>
        </x-design::notice>
    @endif

    @if ($this->answer()->offer !== null)
        {{-- One offer, for the stack: it moves each service onto the version
             its build pins, and the confirmation names which services. --}}
        <x-design::card>
            <x-design::body>{{ trans_choice('updates.would_change', $this->answer()->offer->changing()->count()) }}</x-design::body>
            <x-design::action label="{{ trans_choice('updates.take_them', $this->answer()->offer->changing()->count()) }}" tap="wouldYouLike()" />
        </x-design::card>
    @endif

    @if ($this->answer()->inUse !== null)
        {{-- What the release in use changed. Its build carries the pins, so
             this is the reason an update would move anything — and whether the
             household will notice is what makes it a decision. --}}
        <x-design::card>
            <x-design::strong>{{ __('updates.what_it_changed', ['version' => $this->answer()->inUse->version]) }}</x-design::strong>

            @if ($this->answer()->inUse->deliversSaid !== null)
                <x-design::body>{{ $this->answer()->inUse->deliversSaid }}</x-design::body>
            @else
                <x-design::note>{{ __('updates.delivers_unsaid') }}</x-design::note>
            @endif

            <x-design::note>
                @if ($this->answer()->inUse->theHouseholdWouldNotice)
                    {{ __('updates.would_be_noticed') }}
                @else
                    {{ __('updates.would_not_be_noticed') }}
                @endif
            </x-design::note>
        </x-design::card>
    @elseif ($this->answer()->notesWithheld->said !== '')
        {{-- The record does not describe the running build: notes not written
             yet, or notes out of step with it. Said as that, the way the
             versions screen says it, rather than drawn as what it changed. --}}
        <x-design::card>
            <x-design::strong>{{ __($this->answer()->notesWithheld->said) }}</x-design::strong>
            <x-design::body>{{ __($this->answer()->notesWithheld->meansSaid) }}</x-design::body>
        </x-design::card>
    @endif

    {{-- Every release the stack's record holds, newest first. History up to
         the release running, drawn as history: nothing here is offered, and
         nothing is taken from this list. --}}
    <x-design::heading>{{ __('updates.history') }}</x-design::heading>

    @forelse ($this->answer()->history as $release)
        <x-design::card>
            <x-design::strong>{{ $release->version }}</x-design::strong>

            @if ($release->wasWithdrawn)
                <x-design::note>{{ __('updates.withdrawn') }}</x-design::note>
            @endif

            {{-- Said as the stack said it: this app composes nothing, and a
                 release the stack had nothing to say about says so rather than
                 drawing a blank. --}}
            @if ($release->deliversSaid !== null)
                <x-design::body>{{ $release->deliversSaid }}</x-design::body>
            @else
                <x-design::note>{{ __('updates.delivers_unsaid') }}</x-design::note>
            @endif

            <x-design::note>
                @if ($release->theHouseholdWouldNotice)
                    {{ __('updates.would_be_noticed') }}
                @else
                    {{ __('updates.would_not_be_noticed') }}
                @endif
            </x-design::note>
        </x-design::card>
    @empty
        <x-design::body>{{ __('updates.no_history') }}</x-design::body>
    @endforelse

    {{-- What became of the update taken here, per service, below what is
         waiting. Drawn off the update's own report, never off the reading
         above: a plain reading does not carry how each service took an
         update, so a section drawn off it would always say none was
         taken. --}}
    <x-design::heading>{{ __('updates.last_update') }}</x-design::heading>

    @if (! $this->lastUpdate()->went->cameBack())
        <x-operator::what-stood-in-the-way
            :settings-would-not-open="$this->theSettingsWouldNotOpen"
            :went="$this->lastUpdate()->went"
            :sign-in-goes-to="$this->goes()->signIn()"
        />
    @elseif ($this->lastUpdate()->isWorking)
        <x-design::standing
            :said="__('updates.still_updating')"
            tone="working"
        />
    @elseif ($this->lastUpdate()->hasEnded)
        {{-- Not a failure: it may well have worked, and the reading above
             is where to look. --}}
        <x-design::notice tone="unknown">
            <x-design::strong>{{ __('updates.no_outcome') }}</x-design::strong>
            <x-design::body>{{ __('updates.no_outcome_action') }}</x-design::body>
        </x-design::notice>
    @elseif (! $this->lastUpdate()->wasTaken)
        <x-design::body>{{ __('updates.nothing_applied') }}</x-design::body>
    @else
        @if ($this->lastUpdate()->didNotArrive > 0)
            <x-design::notice tone="trouble">
                <x-design::strong>{{ trans_choice('updates.did_not_arrive', $this->lastUpdate()->didNotArrive) }}</x-design::strong>
            </x-design::notice>
        @endif

        @if ($this->lastUpdate()->anythingUnanswered)
            {{-- Apart from the line above, and not a louder version of it. A
                 service that would not come back up is something to fix; one
                 that started and never answered is something the stack does
                 not know about, and sends somebody to a different place. --}}
            <x-design::body>{{ __('updates.unanswered') }}</x-design::body>
        @endif

        @forelse ($this->lastUpdate()->applied as $took)
            <x-design::card>
                <x-design::strong>{{ $took->service }}</x-design::strong>

                {{-- Which of the four, on the row. Flattened into
                     *failed*, the three ways of not arriving send an operator
                     to look in the wrong place. --}}
                <x-design::body>{{ __($took->endingSaid) }}</x-design::body>

                {{-- Which way back, named, on every service the update moved,
                     whether it arrived or not: each one can be undone. A
                     rollback and a restore are not one offer, and the app says
                     the one the stack named rather than the word they have in
                     common. --}}
                <x-design::note>{{ __($took->undoSaid) }}</x-design::note>

                @if ($took->undoCarriesTheDataWithIt)
                    {{-- The difference worth knowing before agreeing: a
                         restore undoes more than the update did. --}}
                    <x-design::note>{{ __('updates.undo_carries_data') }}</x-design::note>
                @endif
            </x-design::card>
        @empty
            <x-design::body>{{ __('updates.touched_nothing') }}</x-design::body>
        @endforelse
    @endif

    {{-- Offered whether or not the reading came back: somebody watching a
         stuck download or an update land is looking at a screen they want to
         ask again, and a screen that can only be refreshed by leaving it and
         coming back is one they cannot reason about.

         Last, under what it is about, for the health screen's reason: somebody
         who has just changed something scrolls to the end of what they were
         reading, and that is where they want to ask whether it took. Tonal,
         because taking an update is the screen's one way forward. --}}
    <x-design::action label="{{ __('health.ask_again') }}" tap="again()" tone="tonal" />
@endif
</x-operator::content>
@else
    <x-operator::what-stopped-the-reading
        :settings-would-not-open="$this->theSettingsWouldNotOpen"
        :went="$this->answer()->went"
        :sign-in-goes-to="$this->goes()->signIn()"
    />
@endif

<x-operator::screen-closes :goes="$this->goes()" :here="$this->itsTab()" />
