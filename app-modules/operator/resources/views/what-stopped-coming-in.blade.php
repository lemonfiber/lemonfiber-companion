<x-operator::screen-opens :title="$this->stack()->name()->shown()" />

<x-operator::what-stopped-the-reading
    :signed-in="$this->answer()->isSignedIn"
    :met="$this->answer()->met"
    :remedy="$this->answer()->remedy"
    :sign-in-goes-to="$this->goes()->signIn()"
/>

@if ($this->answer()->isSignedIn && $this->answer()->met === '')
<native:column class="w-full gap-4 px-6 py-4">
    {{-- How many stopped, said before the list, so an operator who opened
         this because somebody in the house asked them to does not have to
         count rows. --}}
    <x-operator::emphasis>
        {{ trans_choice('health.stuck_count', $this->howMany()) }}
    </x-operator::emphasis>

    {{-- Whether this is the whole of what the stack holds. Rendered in both
         cases rather than only when something is missing: a screen that is
         silent when a list is whole teaches an operator to read silence,
         and silence is also what a screen that forgot the flag produces. --}}
    <x-operator::note>{{ __($this->answer()->shownSaid) }}</x-operator::note>

    @forelse ($this->answer()->stalled as $item)
        <x-operator::entry>
            <x-operator::emphasis>{{ $item->title }}</x-operator::emphasis>

            {{-- N2-R9: where it stopped and who has it. Both, because
                 either alone strands the operator — a stage with no service
                 is a problem with nowhere to go, and a service with no
                 stage sends somebody to the download client for a title the
                 indexer never found a release for. --}}
            <native:text>{{ __($item->stageSaid) }}</native:text>
            <x-operator::note>
                {{ __('health.stuck_in', ['service' => $item->service]) }}
            </x-operator::note>

            @unless ($item->stillMoving)
                {{-- The two ends of the pipeline, where nothing is going to
                     move it by itself. Said on the row rather than by
                     sorting, because the operator is looking for a title
                     and not for a category. --}}
                <x-operator::note>{{ __('health.stuck_for_good') }}</x-operator::note>
            @endunless
        </x-operator::entry>
    @empty
        {{-- Not the same screen as a stack that could not be asked. Nothing
             stuck is the answer the operator wants, and saying so is what
             tells it apart from the obstacle branch above. --}}
        <x-operator::emphasis>{{ __('health.nothing_stopped') }}</x-operator::emphasis>
        <native:text>{{ __('health.nothing_stopped_action') }}</native:text>
    @endforelse
</native:column>
@endif

<x-operator::screen-closes :goes="$this->goes()" here="health" />
