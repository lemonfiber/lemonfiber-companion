<x-operator::screen-opens :title="$this->stack()->name()->shown()" />

@if ($this->answer()->went->cameBack())
<x-operator::content>
    {{-- How many stopped, said before the list, so an operator who opened
         this because somebody in the house asked them to does not have to
         count rows. --}}
    <x-design::title>{{ trans_choice($this->answer()->countSaid, $this->howMany()) }}</x-design::title>

    {{-- Whether this is the whole of what the stack holds. Rendered in both
         cases rather than only when something is missing: a screen that is
         silent when a list is whole teaches an operator to read silence,
         and silence is also what a screen that forgot the flag produces. --}}
    <x-design::note>{{ __($this->answer()->shownSaid) }}</x-design::note>

    {{-- What the stack could not look into, before the rows. An empty listing
         from a queue it could not reach is not good news, so this is its own
         answer rather than a footnote under one. --}}
    @unless ($this->answer()->unreached === [])
        <x-design::notice tone="unknown">
            <x-design::strong>{{ __('health.could_not_reach') }}</x-design::strong>
            <x-design::body>{{ __('health.could_not_reach_explained') }}</x-design::body>
        </x-design::notice>

        <x-design::section>
            @forelse ($this->answer()->unreached as $limit)
                <x-design::row :headline="$limit->what" :supporting="$limit->because" />
            @empty
                {{-- Unreachable while the branch above guards it. A stack that
                     reached everything it manages has nothing to add here: the
                     plain count above already says so. --}}
            @endforelse
        </x-design::section>
    @endunless

    @forelse ($this->answer()->stalled as $item)
        <x-design::card>
            <x-design::strong>{{ $item->title }}</x-design::strong>

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
            <x-design::body>{{ __('health.at_stage', ['stage' => $item->stage]) }}</x-design::body>
            <x-design::body>{{ __($item->stageSaid) }}</x-design::body>
            <x-operator::gloss :gloss="$this->gloss($item->stage)" />
            <x-design::note>{{ __('health.stuck_in', ['service' => $item->service]) }}</x-design::note>

            @unless ($item->stillMoving)
                {{-- The two ends of the pipeline, where nothing is going to
                     move it by itself. Said on the card rather than by
                     sorting, because the operator is looking for a title
                     and not for a category. --}}
                <x-design::note>{{ __('health.stuck_for_good') }}</x-design::note>
            @endunless

            {{-- Where it got to, followed through every service rather than
                 the one it stopped in. Last on the card, under what it is
                 about. --}}
            <x-design::link label="{{ __('health.trace.road_in', ['item' => $item->title]) }}" :goes="$this->traceOf($item->title)" />
        </x-design::card>
    @empty
        {{-- Not the same screen as a stack that could not be asked. Nothing
             stuck is the answer the operator wants, and saying so is what
             tells it apart from the obstacle branch. --}}
        @if ($this->answer()->unreached === [])
            <x-design::standing :said="__('health.nothing_stopped')" tone="fine" :note="__('health.nothing_stopped_action')" />
        @endif
    @endforelse

    {{-- Offered whether or not the reading came back: somebody watching a
         stuck download is looking at a screen they want to ask again, and a
         screen that can only be refreshed by leaving it and coming back is
         one they cannot reason about.

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

<x-operator::screen-closes :goes="$this->goes()" here="health" />
