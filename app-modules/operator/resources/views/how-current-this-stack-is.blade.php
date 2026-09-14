<native:column class="w-full gap-4 p-6">
    <native:text class="text-lg font-bold">{{ $this->stack()->name()->shown() }}</native:text>

    @unless ($this->answer()->isSignedIn)
        {{-- N1-R44: the session has ended, so nothing was asked and there is
             nothing to report. The remedy is a screen rather than a sentence. --}}
        <native:text>{{ __('connection.session_has_ended') }}</native:text>
        <native:button
            label="{{ __('connection.sign_in') }}"
            @navigate="{{ $this->goes()->signIn() }}"
        />
    @elseif ($this->answer()->met !== '')
        {{-- N1-R10: what stood in the way, and what to do about it. Both come
             off the obstacle, so this screen cannot describe a condition
             differently from the one next to it. --}}
        <native:text class="font-bold">{{ __($this->answer()->met) }}</native:text>
        <native:text>{{ __($this->answer()->remedy) }}</native:text>

        {{-- N1-R3: the action is offered and the failure reported, rather than
             the action being taken away because the stack is unreachable. --}}
        <native:button label="{{ __('health.ask_again') }}" @tap="again()" />
    @else
        {{-- N2-R15: the answer, first. Current, waiting, or not looked at
             recently — the stack's own word, not one worked out here from two
             version strings. --}}
        <native:text class="font-bold">{{ __($this->answer()->howSaid) }}</native:text>

        {{-- What it is on. Rendered in every state rather than only where
             something is waiting: a screen silent about the version teaches an
             operator to read silence, and silence is also what a screen that
             forgot the field produces. --}}
        <native:text class="text-sm">
            {{ __('updates.running_on', ['version' => $this->answer()->running]) }}
        </native:text>

        @if ($this->answer()->runningWasWithdrawn)
            {{-- N2-R16: a stack running a release that has since been taken
                 back is something the operator has to be told. Left out of what
                 is offered below, and said here — dropping it from both would
                 leave them reading a screen that says nothing is wrong. --}}
            <native:text class="font-bold">{{ __('updates.running_withdrawn') }}</native:text>
        @endif

        @forelse ($this->answer()->waiting as $release)
            <native:column class="w-full gap-1">
                <native:text class="font-bold">{{ $release->version }}</native:text>

                {{-- N2-R16: whether somebody in the house would see the
                     difference. This is what makes the update a decision rather
                     than a chore, so it is on the row and not in a footnote. --}}
                <native:text class="text-sm">
                    @if ($release->theHouseholdWouldNotice)
                        {{ __('updates.would_be_noticed') }}
                    @else
                        {{ __('updates.would_not_be_noticed') }}
                    @endif
                </native:text>
            </native:column>
        @empty
            {{-- N2-R20: nothing is offered where the stack reported none
                 waiting, and the empty state says so rather than leaving a
                 blank where a list belongs. --}}
            <native:text>{{ __('updates.nothing_waiting') }}</native:text>
        @endforelse
    @endunless
</native:column>
