@use('Modules\Kernel\Api\WhatWasDecided')
@use('Modules\Stacks\Api\AStacksScreen')
<x-wayfinding::stack-opens :title="$this->stack()->name()->shown()" :stacks="$this->stacksToChooseFrom()" :choosing="$this->choosingAStack" />

@if ($this->answer()->went->cameBack())
<x-operator::content>
    @if ($this->answer()->waitsForTheStack)
        {{-- What the phone kept, said for what it is: how long ago it was
             read, and above it what stopped the reading asked for now. Asking
             again is the column's own, below. --}}
        @if (! $this->answer()->askedNow->cameBack())
            <x-operator::what-stood-in-the-way
                :settings-would-not-open="$this->theSettingsWouldNotOpen"
                :went="$this->answer()->askedNow"
                ask-again=""
                :goes="$this->goes()"
            />
        @endif
        <x-design::note>{{ __('health.summary.as_of', ['ago' => trans_choice($this->answer()->readAgo->said, $this->answer()->readAgo->count)]) }}</x-design::note>
    @endif

    {{-- What is waiting on the operator, said before the list. An
         operator who opened this screen because somebody in the house asked
         them to should not have to count rows to find out whether anything
         needs them. --}}
    <x-design::title>{{ trans_choice('household.waiting_count', $this->answer()->waiting) }}</x-design::title>

    @if ($this->answer()->waitsForTheStack && $this->answer()->waiting > 0 && $this->turningDown() === null)
        {{-- Approving and turning down are drawn on every row that waits and
             cannot be used while the reading is one the phone kept, never
             hidden. Said once, above the rows, rather than on each: the same
             sentence on every card is one line read aloud again and again. --}}
        <x-design::note>{{ __('connection.usable_once_the_stack_answers', ['ago' => trans_choice($this->answer()->readAgo->said, $this->answer()->readAgo->count)]) }}</x-design::note>
    @endif

    @if ($this->turningDown() !== null)
        {{-- A refusal owes the person who asked a sentence, and this is
             where it is written. Its own frame rather than a field on the row,
             because what an operator is doing here is composing something
             somebody will read — and because a screen that turned a request
             down from the row it sits on would be one tap from doing it by
             accident. --}}
        <x-design::card>
            <x-design::heading>{{ __('household.turning_down', ['title' => $this->turningDown()->title]) }}</x-design::heading>
            <x-design::body>{{ __('household.turning_down_owes', ['who' => $this->turningDown()->by]) }}</x-design::body>

            <native:outlined-text-input
                native:model="because"
                label="{{ __('household.reason_label') }}"
                placeholder="{{ __('household.reason_placeholder') }}"
                supporting="{{ __('household.reason_is_shown') }}"
            />
        </x-design::card>

        <x-operator::offered-action
            label="{{ __('household.turn_it_down') }}"
            :disabled="! $this->mayDecline() || $this->answer()->waitsForTheStack"
            tap="decline()"
            :offer="$this->offered(WhatWasDecided::Decline)"
        />
        @if ($this->answer()->waitsForTheStack)
            <x-design::note>{{ __('connection.usable_once_the_stack_answers', ['ago' => trans_choice($this->answer()->readAgo->said, $this->answer()->readAgo->count)]) }}</x-design::note>
        @endif
        <x-design::action label="{{ __('household.never_mind') }}" tap="neverMind()" tone="tonal" />
    @else
    @forelse ($this->answer()->requests as $request)
        <x-design::card>
            <x-design::strong>{{ $request->title }}</x-design::strong>
            <x-design::note>{{ __('household.asked_by', ['who' => $request->by]) }}</x-design::note>

            {{-- One fact and two sentences: the size, and whether anybody
                 measured it. The key carries the labelling so a translator
                 owns it; the figure is a whole number under a thousand, so no
                 locale's separator can be wrong here. --}}
            <x-design::note>{{ __($request->sizeSaid, ['size' => $request->sizeFigure, 'unit' => $request->sizeUnit === '' ? '' : __($request->sizeUnit)]) }}</x-design::note>

            <x-design::body>{{ __($request->standing) }}</x-design::body>

            {{-- A refused request carries the reason that was given.
                 `declined` on its own is the answer that sends somebody to ask
                 their operator in person. --}}
            @if ($request->refusedReason !== '')
                <x-design::note>{{ __('household.refused_because', ['reason' => $request->refusedReason]) }}</x-design::note>

                @if ($request->refusedAt !== '')
                    {{-- In the stack's own words rather than this phone's
                         timezone, so two people in the house do not
                         disagree about when it happened. --}}
                    <x-design::note>{{ __('household.refused_at', ['when' => $request->refusedAt]) }}</x-design::note>
                @endif
            @endif

            @if ($request->wantsADecision)
                {{-- Approvable and refusable from here. The approval is the
                     filled one: it is what the person who asked is hoping
                     for, and it owes them nothing but the thing itself.
                     Turning one down is tonal because it opens a question
                     rather than settling one. --}}
                <x-operator::offered-action
                    label="{{ __('household.approve') }}"
                    answers-to="{{ __('household.approve_that', ['title' => $request->title]) }}"
                    tap="approve('{{ $request->number }}')"
                    :offer="$this->offered(WhatWasDecided::Approve)"
                    :disabled="$this->answer()->waitsForTheStack"
                />
                <x-operator::offered-action
                    label="{{ __('household.turn_down') }}"
                    answers-to="{{ __('household.turn_down_that', ['title' => $request->title]) }}"
                    tap="wouldDecline('{{ $request->number }}')"
                    :offer="$this->offered(WhatWasDecided::Decline)"
                    tone="tonal"
                    :disabled="$this->answer()->waitsForTheStack"
                />
            @endif
        </x-design::card>
    @empty
        {{-- Not the same screen as a stack that could not be asked. A quiet
             week is an answer, and saying so is what tells it apart from
             the obstacle branch. --}}
        <x-design::standing :said="__('household.nothing_asked')" tone="fine" :note="__('household.nothing_asked_action')" />
    @endforelse
    @endif

    {{-- Offered whether or not the reading came back: somebody watching a
         request land is looking at a screen they want to ask again, and a
         screen that can only be refreshed by leaving it and coming back is
         one they cannot reason about.

         Last, under what it is about, for the health screen's reason: somebody
         who has just changed something scrolls to the end of what they were
         reading, and that is where they want to ask whether it took. --}}
    <x-design::action label="{{ __('health.ask_again') }}" tap="askAgain()" tone="tonal" />
</x-operator::content>
@else
    <x-operator::what-stopped-the-reading
        :settings-would-not-open="$this->theSettingsWouldNotOpen"
        :went="$this->answer()->went"
        :goes="$this->goes()"
    />
@endif

<x-operator::screen-closes :goes="$this->goes()" :here="$this->itsTab()" :marks="$this->marks()" />
