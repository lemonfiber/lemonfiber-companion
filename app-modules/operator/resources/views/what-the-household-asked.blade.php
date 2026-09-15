<native:column class="w-full gap-4 p-6">
    <native:text class="text-lg font-bold">{{ $this->stack()->name()->shown() }}</native:text>

    @unless ($this->answer()->isSignedIn)
        {{-- N1-R44: the session has ended, so nothing was asked and there is
             nothing to report. The remedy is a screen rather than a sentence. --}}
        <native:text>{{ __('connection.session_has_ended') }}</native:text>
        <native:button
            label="{{ __('connection.sign_in') }}"
            @navigate="$this->goes()->signIn()"
        />
    @elseif ($this->answer()->met !== '')
        {{-- N1-R10: what stood in the way, and what to do about it. Both come
             off the obstacle, so this screen cannot describe a condition
             differently from the one next to it. --}}
        <native:text class="font-bold">{{ __($this->answer()->met) }}</native:text>
        <native:text>{{ __($this->answer()->remedy) }}</native:text>

        {{-- N1-R3: the action is offered and the failure is reported, rather
             than the action being taken away because the stack is unreachable.
             Without it the only way back is leaving and returning, which
             `N1-R27` names separately as the thing a screen must not rely on. --}}
        <native:button label="{{ __('health.ask_again') }}" @tap="again()" />
    @else
        {{-- N2-R11: what is waiting on the operator, said before the list. An
             operator who opened this screen because somebody in the house asked
             them to should not have to count rows to find out whether anything
             needs them. --}}
        <native:text class="font-bold">
            {{ trans_choice('household.waiting_count', $this->answer()->waiting) }}
        </native:text>

        @forelse ($this->answer()->requests as $request)
            <native:column class="w-full gap-1">
                <native:text class="font-bold">{{ $request->title }}</native:text>
                <native:text class="text-sm">{{ __('household.asked_by', ['who' => $request->by]) }}</native:text>

                {{-- D7-R3 and D7-R4 are one fact and two sentences: the size,
                     and whether anybody measured it. The key carries the
                     labelling so a translator owns it; the figure is a whole
                     number under a thousand, so no locale's separator can be
                     wrong here (L5). --}}
                <native:text class="text-sm">
                    {{ __($request->sizeSaid, [
                        'size' => $request->sizeFigure,
                        'unit' => $request->sizeUnit === '' ? '' : __($request->sizeUnit),
                    ]) }}
                </native:text>

                <native:text>{{ __($request->standing) }}</native:text>

                {{-- N3-R7 and D7-R7: a refused request carries the reason that
                     was given. `declined` on its own is the answer that sends
                     somebody to ask their operator in person, which is the
                     whole thing the requirement exists to prevent. --}}
                @if ($request->refusedReason !== '')
                    <native:text class="text-sm">
                        {{ __('household.refused_because', ['reason' => $request->refusedReason]) }}
                    </native:text>

                    @if ($request->refusedAt !== '')
                        {{-- In the stack's own words rather than this phone's
                             timezone, so two people in the house do not
                             disagree about when it happened. --}}
                        <native:text class="text-sm">
                            {{ __('household.refused_at', ['when' => $request->refusedAt]) }}
                        </native:text>
                    @endif
                @endif
            </native:column>
        @empty
            {{-- Not the same screen as a stack that could not be asked. A quiet
                 week is an answer, and saying so is what tells it apart from
                 the obstacle branch above. --}}
            <native:text class="font-bold">{{ __('household.nothing_asked') }}</native:text>
            <native:text>{{ __('household.nothing_asked_action') }}</native:text>
        @endforelse
    @endunless

    <native:button label="{{ __('household.see_health') }}" @navigate="$this->goes()->health()" />
</native:column>
