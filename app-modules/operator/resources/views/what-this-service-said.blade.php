<native:column class="w-full gap-4 p-6">
    {{-- N2-R10: the service is named, at the top, because every other sentence
         on this screen is about it. --}}
    <native:text class="text-lg font-bold">
        {{ __('health.logs_for', ['service' => $this->service()->named()]) }}
    </native:text>

    @unless ($this->isSignedIn())
        {{-- N1-R44: the session has ended, so nothing was read and there is
             nothing to report. The remedy is a screen rather than a sentence. --}}
        <native:text>{{ __('connection.session_has_ended') }}</native:text>
        <native:button
            label="{{ __('connection.sign_in') }}"
            @navigate="{{ $this->goes()->signIn() }}"
        />
    @elseif ($this->met() !== '')
        {{-- N1-R10: what stood in the way, and what to do about it. Both come
             off the obstacle, so this screen cannot describe a condition
             differently from the one next to it. --}}
        <native:text class="font-bold">{{ __($this->met()) }}</native:text>
        <native:text>{{ __($this->remedy()) }}</native:text>
    @else
        {{-- N2-R10: the view is a window rather than the whole, said before the
             lines rather than under them. Both cases have a line — a screen
             silent when the bound cut nothing teaches an operator to read
             silence, and silence is also what a screen that lost the claim
             produces. --}}
        @if ($this->isAWindow())
            <native:text class="text-sm">
                {{ __('health.window_of', ['count' => $this->howManyArrived()]) }}
            </native:text>
        @else
            <native:text class="text-sm">
                {{ __('health.the_whole_of_it', ['count' => $this->howManyArrived()]) }}
            </native:text>
        @endif

        {{-- N2-R10: searchable. Over the window, and the line below says so —
             a search that finds nothing reads as *the service never said it*,
             and what it means is *not in the lines that came back*. --}}
        <native:outlined-text-input
            native:model="looking"
            label="{{ __('health.search_label') }}"
            placeholder="{{ __('health.search_placeholder') }}"
        />
        <native:text class="text-sm">{{ __('health.search_is_over_the_window') }}</native:text>

        @if ($this->isSearching())
            <native:text class="font-bold">
                {{ trans_choice('health.matched_count', $this->howMany()) }}
            </native:text>
        @endif

        @forelse ($this->lines() as $line)
            <native:column class="w-full gap-1">
                {{-- Two branches with a literal class each rather than one
                     element with a computed one (F9). The stream is not a
                     severity — plenty of well-behaved services write progress
                     to `stderr` — so this is weight, not a red mark. --}}
                @if ($line->worthNoticing)
                    <native:text class="font-bold">{{ $line->line }}</native:text>
                @else
                    <native:text>{{ $line->line }}</native:text>
                @endif
                <native:text class="text-sm">
                    @if ($line->hasAMoment)
                        {{ $line->at }}
                    @else
                        {{ __('health.no_moment') }}
                    @endif
                    · {{ __($line->streamSaid) }}
                </native:text>
            </native:column>
        @empty
            @if ($this->isSearching())
                {{-- Not the same as a silent service, and the difference is the
                     whole reason both lines exist. --}}
                <native:text class="font-bold">{{ __('health.nothing_matched') }}</native:text>
                <native:text>{{ __('health.nothing_matched_action') }}</native:text>
            @else
                <native:text class="font-bold">{{ __('health.service_said_nothing') }}</native:text>
                <native:text>{{ __('health.service_said_nothing_action') }}</native:text>
            @endif
        @endforelse
    @endunless

    <native:button label="{{ __('health.back_to_the_stack') }}" @navigate="{{ $this->goes()->health() }}" />
</native:column>
