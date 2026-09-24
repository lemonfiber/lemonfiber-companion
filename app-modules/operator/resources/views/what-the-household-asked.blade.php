<x-operator::screen-opens :title="$this->stack()->name()->shown()" />

@if ($this->answer()->went->cameBack())
<x-operator::content>
    {{-- What is waiting on the operator, said before the list. An
         operator who opened this screen because somebody in the house asked
         them to should not have to count rows to find out whether anything
         needs them. --}}
    <x-operator::emphasis>
        {{ trans_choice('household.waiting_count', $this->answer()->waiting) }}
    </x-operator::emphasis>

    @if ($this->turningDown() !== null)
        {{-- A refusal owes the person who asked a sentence, and this is
             where it is written. Its own frame rather than a field on the row,
             because what an operator is doing here is composing something
             somebody will read — and because a screen that turned a request
             down from the row it sits on would be one tap from doing it by
             accident. --}}
        <x-operator::heading>
            {{ __('household.turning_down', ['title' => $this->turningDown()->title]) }}
        </x-operator::heading>
        <native:text>{{ __('household.turning_down_owes', ['who' => $this->turningDown()->by]) }}</native:text>

        <native:outlined-text-input
            native:model="because"
            label="{{ __('household.reason_label') }}"
            placeholder="{{ __('household.reason_placeholder') }}"
            supporting="{{ __('household.reason_is_shown') }}"
        />

        <x-operator::action
            label="{{ __('household.turn_it_down') }}"
            :disabled="! $this->mayDecline()"
            tap="decline()"
        />
        <x-operator::quiet-action label="{{ __('household.never_mind') }}" tap="neverMind()" />
    @else
    @forelse ($this->answer()->requests as $request)
        <x-operator::entry>
            <x-operator::emphasis>{{ $request->title }}</x-operator::emphasis>
            <x-operator::note>{{ __('household.asked_by', ['who' => $request->by]) }}</x-operator::note>

            {{-- One fact and two sentences: the size,
                 and whether anybody measured it. The key carries the
                 labelling so a translator owns it; the figure is a whole
                 number under a thousand, so no locale's separator can be
                 wrong here (L5). --}}
            <x-operator::note>
                {{ __($request->sizeSaid, [
                    'size' => $request->sizeFigure,
                    'unit' => $request->sizeUnit === '' ? '' : __($request->sizeUnit),
                ]) }}
            </x-operator::note>

            <native:text>{{ __($request->standing) }}</native:text>

            {{-- A refused request carries the reason that
                 was given. `declined` on its own is the answer that sends
                 somebody to ask their operator in person, which is the
                 whole thing the requirement exists to prevent. --}}
            @if ($request->refusedReason !== '')
                <x-operator::note>
                    {{ __('household.refused_because', ['reason' => $request->refusedReason]) }}
                </x-operator::note>

                @if ($request->refusedAt !== '')
                    {{-- In the stack's own words rather than this phone's
                         timezone, so two people in the house do not
                         disagree about when it happened. --}}
                    <x-operator::note>
                        {{ __('household.refused_at', ['when' => $request->refusedAt]) }}
                    </x-operator::note>
                @endif
            @endif

            @if ($request->wantsADecision)
                {{-- Approvable and refusable from the app, which for a
                     long time this screen said and did not offer. The approval
                     is the filled one: it is what the person who asked is
                     hoping for, and it owes them nothing but the thing itself.
                     Turning one down is quiet because it opens a question
                     rather than settling one. --}}
                <x-operator::action
                    label="{{ __('household.approve') }}"
                    answers-to="{{ __('household.approve_that', ['title' => $request->title]) }}"
                    tap="approve('{{ $request->number }}')"
                />
                <x-operator::quiet-action
                    label="{{ __('household.turn_down') }}"
                    tap="wouldDecline('{{ $request->number }}')"
                />
            @endif
        </x-operator::entry>
    @empty
        {{-- Not the same screen as a stack that could not be asked. A quiet
             week is an answer, and saying so is what tells it apart from
             the obstacle branch above. --}}
        <x-operator::emphasis>{{ __('household.nothing_asked') }}</x-operator::emphasis>
        <native:text>{{ __('household.nothing_asked_action') }}</native:text>
    @endforelse
    @endif

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
</x-operator::content>
@else
    <x-operator::what-stopped-the-reading
        :went="$this->answer()->went"
        :sign-in-goes-to="$this->goes()->signIn()"
    />
@endif

<x-operator::screen-closes :goes="$this->goes()" here="health" />
