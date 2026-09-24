<x-operator::screen-opens :title="$this->stack()->name()->shown()" />

@if ($this->answer()->went->cameBack())
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

            {{-- Where it stopped and who has it. Both, because
                 either alone strands the operator — a stage with no service
                 is a problem with nowhere to go, and a service with no
                 stage sends somebody to the download client for a title the
                 indexer never found a release for.

                 The stage is the stack's own word, drawn as it came and
                 untranslated, with the plain sentence beside it rather than
                 in its place: the word is the one the contract carries, and
                 a phone that swapped it for its own would be speaking a
                 vocabulary nobody else does. --}}
            <native:text>{{ __('health.at_stage', ['stage' => $item->stage]) }}</native:text>
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
</native:column>
@else
    <x-operator::what-stopped-the-reading
        :went="$this->answer()->went"
        :sign-in-goes-to="$this->goes()->signIn()"
    />
@endif

<x-operator::screen-closes :goes="$this->goes()" here="health" />
