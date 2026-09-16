<x-operator::screen-opens :title="$this->stack()->name()->shown()" />

@if ($this->answer()->went->cameBack())
<native:column class="w-full gap-4 px-6 py-4">
    {{-- N2-R11: what is waiting on the operator, said before the list. An
         operator who opened this screen because somebody in the house asked
         them to should not have to count rows to find out whether anything
         needs them. --}}
    <x-operator::emphasis>
        {{ trans_choice('household.waiting_count', $this->answer()->waiting) }}
    </x-operator::emphasis>

    @forelse ($this->answer()->requests as $request)
        <x-operator::entry>
            <x-operator::emphasis>{{ $request->title }}</x-operator::emphasis>
            <x-operator::note>{{ __('household.asked_by', ['who' => $request->by]) }}</x-operator::note>

            {{-- D7-R3 and D7-R4 are one fact and two sentences: the size,
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

            {{-- N3-R7 and D7-R7: a refused request carries the reason that
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
        </x-operator::entry>
    @empty
        {{-- Not the same screen as a stack that could not be asked. A quiet
             week is an answer, and saying so is what tells it apart from
             the obstacle branch above. --}}
        <x-operator::emphasis>{{ __('household.nothing_asked') }}</x-operator::emphasis>
        <native:text>{{ __('household.nothing_asked_action') }}</native:text>
    @endforelse
</native:column>
@else
    <x-operator::what-stopped-the-reading
        :went="$this->answer()->went"
        :sign-in-goes-to="$this->goes()->signIn()"
    />
@endif

<x-operator::screen-closes :goes="$this->goes()" here="health" />
