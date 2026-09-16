<x-operator::screen-opens :title="__('health.logs_for', ['service' => $this->service()->named()])" />

<x-operator::what-stopped-the-reading
    :signed-in="$this->answer()->isSignedIn"
    :met="$this->answer()->met"
    :remedy="$this->answer()->remedy"
    :sign-in-goes-to="$this->goes()->signIn()"
/>

@if ($this->answer()->isSignedIn && $this->answer()->met === '')
<native:column class="w-full gap-4 px-6 py-4">
    {{-- N2-R10: the view is a window rather than the whole, said before the
         lines rather than under them. Both cases have a line — a screen
         silent when the bound cut nothing teaches an operator to read
         silence, and silence is also what a screen that lost the claim
         produces. --}}
    @if ($this->answer()->isAWindow)
        <x-operator::note>
            {{ __('health.window_of', ['count' => $this->answer()->arrived]) }}
        </x-operator::note>
    @else
        <x-operator::note>
            {{ __('health.the_whole_of_it', ['count' => $this->answer()->arrived]) }}
        </x-operator::note>
    @endif

    {{-- N2-R10: searchable. Over the window, and the line below says so —
         a search that finds nothing reads as *the service never said it*,
         and what it means is *not in the lines that came back*. --}}
    <native:outlined-text-input
        native:model="looking"
        label="{{ __('health.search_label') }}"
        placeholder="{{ __('health.search_placeholder') }}"
    />
    <x-operator::note>{{ __('health.search_is_over_the_window') }}</x-operator::note>

    @if ($this->answer()->isSearching)
        <x-operator::emphasis>
            {{ trans_choice('health.matched_count', $this->howMany()) }}
        </x-operator::emphasis>
    @endif

    @forelse ($this->answer()->lines as $line)
        <x-operator::entry>
            {{-- Two branches with a literal class each rather than one
                 element with a computed one (F9). The stream is not a
                 severity — plenty of well-behaved services write progress
                 to `stderr` — so this is weight, not a red mark. --}}
            @if ($line->worthNoticing)
                <x-operator::emphasis>{{ $line->line }}</x-operator::emphasis>
            @else
                <native:text>{{ $line->line }}</native:text>
            @endif
            <x-operator::note>
                @if ($line->hasAMoment)
                    {{ $line->at }}
                @else
                    {{ __('health.no_moment') }}
                @endif
                · {{ __($line->streamSaid) }}
            </x-operator::note>
        </x-operator::entry>
    @empty
        @if ($this->answer()->isSearching)
            {{-- Not the same as a silent service, and the difference is the
                 whole reason both lines exist. --}}
            <x-operator::emphasis>{{ __('health.nothing_matched') }}</x-operator::emphasis>
            <native:text>{{ __('health.nothing_matched_action') }}</native:text>
        @else
            <x-operator::emphasis>{{ __('health.service_said_nothing') }}</x-operator::emphasis>
            <native:text>{{ __('health.service_said_nothing_action') }}</native:text>
        @endif
    @endforelse
</native:column>
@endif

<x-operator::screen-closes :goes="$this->goes()" here="services" />
