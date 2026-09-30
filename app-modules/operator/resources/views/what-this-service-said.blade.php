<x-operator::screen-opens :title="__('health.logs_for', ['service' => $this->called()])" />

@if ($this->answer()->went->cameBack())
<x-operator::content from-the-end>
    {{-- The code the road here carried, from a finding or from how the
         service stopped: for whoever helps, and said above the lines, where
         somebody finding out what happened is already reading. --}}
    @if ($this->reported() !== '')
        <x-design::note>{{ __('health.the_check_reported', ['code' => $this->reported()]) }}</x-design::note>
    @endif
    @if ($this->exited() !== '')
        <x-design::note>{{ __('health.it_stopped_with_exit_code', ['code' => $this->exited()]) }}</x-design::note>
    @endif

    {{-- The view is a window rather than the whole, said before the
         lines rather than under them. Both cases have a line — a screen
         silent when the bound cut nothing teaches an operator to read
         silence, and silence is also what a screen that lost the claim
         produces. --}}
    @if ($this->answer()->isAWindow)
        <x-design::note>{{ __('health.window_of', ['count' => $this->answer()->arrived]) }}</x-design::note>
    @else
        <x-design::note>{{ __('health.the_whole_of_it', ['count' => $this->answer()->arrived]) }}</x-design::note>
    @endif

    {{-- Searchable. Over the window, and the line below says so —
         a search that finds nothing reads as *the service never said it*,
         and what it means is *not in the lines that came back*. --}}
    <native:outlined-text-input
        native:model="looking"
        label="{{ __('health.search_label') }}"
        placeholder="{{ __('health.search_placeholder') }}"
    />
    <x-design::note>{{ __('health.search_is_over_the_window') }}</x-design::note>

    @if ($this->answer()->isSearching)
        <x-design::strong>{{ trans_choice('health.matched_count', $this->howMany()) }}</x-design::strong>
    @endif

    {{-- The lines on one card, as the service wrote them, each with the time
         on the phone's clock under it, and which stream it came out of where
         that was the error stream. A screen reader hears the moment as the
         service wrote it. --}}
    <x-design::card>
        @forelse ($this->answer()->lines as $line)
            @if ($line->folded > 0)
                {{-- A run of lines holding no letters, folded into one row
                     that opens to show them. --}}
                <x-design::link
                    label="{{ trans_choice('health.decorative_lines', $line->folded) }}"
                    tap="unfold({{ $line->fold }})"
                />
            @else
                {{-- The stream is not a severity — plenty of well-behaved
                     services write progress to `stderr` — so a line worth
                     noticing is drawn in weight, not with a mark. --}}
                @if ($line->worthNoticing)
                    <x-design::strong>{{ $line->line }}</x-design::strong>
                @else
                    <x-design::verbatim>{{ $line->line }}</x-design::verbatim>
                @endif
                @if ($line->hasAMoment && $line->streamSaid !== '')
                    <x-design::note :answers-to="$line->atInFull . ' · ' . __($line->streamSaid)">{{ $line->at }} · {{ __($line->streamSaid) }}</x-design::note>
                @elseif ($line->hasAMoment)
                    <x-design::note :answers-to="$line->atInFull">{{ $line->at }}</x-design::note>
                @elseif ($line->streamSaid !== '')
                    <x-design::note>{{ __('health.no_moment') }} · {{ __($line->streamSaid) }}</x-design::note>
                @endif
            @endif
        @empty
            @if ($this->answer()->isSearching)
                {{-- Not the same as a silent service, and the difference is the
                     whole reason both lines exist. --}}
                <x-design::strong>{{ __('health.nothing_matched') }}</x-design::strong>
                <x-design::body>{{ __('health.nothing_matched_action') }}</x-design::body>
            @else
                <x-design::strong>{{ __('health.service_said_nothing') }}</x-design::strong>
                <x-design::body>{{ __('health.service_said_nothing_action') }}</x-design::body>
            @endif
        @endforelse
    </x-design::card>

    {{-- A screen an operator cannot ask again is a screen that relies on
         being left and returned to: somebody watching a stuck download or an
         update land is looking at a screen they want to ask again.

         Last, under what it is about, for the health screen's reason: somebody
         who has just changed something scrolls to the end of what they were
         reading, and that is where they want to ask whether it took. --}}
    <x-design::action label="{{ __('health.ask_again') }}" tap="again()" tone="tonal" />
</x-operator::content>
@else
    <x-operator::what-stopped-the-reading
        :went="$this->answer()->went"
        :sign-in-goes-to="$this->goes()->signIn()"
    />
@endif

<x-operator::screen-closes :goes="$this->goes()" here="services" />
