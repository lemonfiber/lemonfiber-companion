<x-operator::screen-opens :title="$this->stack()->name()->shown()" />

@if ($this->answer()->went->cameBack())
<x-operator::content>
    {{-- Where the machine stands first: it stands where its worst volume
         does, and a volume nobody could read is never drawn as comfortable. --}}
    <x-design::standing :said="__($this->answer()->standsSaid)" :tone="$this->answer()->tone" />

    @if ($this->answer()->halted)
        <x-design::notice>
            <x-design::strong>{{ __('stacks.room.halted') }}</x-design::strong>
        </x-design::notice>
    @endif

    @forelse ($this->answer()->volumes as $volume)
        <x-design::card>
            <x-design::strong>{{ __($volume->holdsSaid) }}</x-design::strong>

            @if ($volume->point !== '')
                <x-design::verbatim>{{ $volume->point }}</x-design::verbatim>
            @endif

            <x-design::body>{{ __($volume->standsSaid) }}</x-design::body>

            @if ($volume->free !== null)
                <x-design::note>{{ __('stacks.room.free', ['figure' => $volume->free->figure, 'unit' => __($volume->free->unit)]) }}</x-design::note>
            @else
                <x-design::note>{{ __('stacks.room.free_unread') }}</x-design::note>
            @endif

            @if ($volume->limit !== null)
                <x-design::note>{{ __('stacks.room.limit', ['figure' => $volume->limit->figure, 'unit' => __($volume->limit->unit)]) }}</x-design::note>
            @endif

            <x-design::note>{{ __('stacks.room.committed', ['figure' => $volume->committed->figure, 'unit' => __($volume->committed->unit)]) }}</x-design::note>

            @if ($volume->projected !== null)
                <x-design::note>{{ __('stacks.room.projected', ['figure' => $volume->projected->figure, 'unit' => __($volume->projected->unit)]) }}</x-design::note>
            @endif

            {{-- A network share answers with what it was last told, so its
                 figures are dated rather than presented as now. --}}
            @if ($volume->agoSaid !== '')
                <x-design::note>{{ __('stacks.room.as_of', ['ago' => trans_choice($volume->agoSaid, $volume->agoCount)]) }}</x-design::note>
            @endif
        </x-design::card>
    @empty
        <x-design::body>{{ __('stacks.room.no_volumes') }}</x-design::body>
    @endforelse

    {{-- Where the room went, by the categories the stack gives: never a
         listing of files. What each takes is at its end; under it, what it
         would take if nothing were shared, only where that differs, and what
         getting it back would cost. --}}
    <x-design::section :label="__('stacks.room.account')">
        @forelse ($this->answer()->account as $line)
            <x-design::row
                :headline="__($line->aboutSaid, ['tree' => $line->tree])"
                :supporting="$line->unshared === null ? __($line->costsSaid) : __('stacks.room.unshared', ['figure' => $line->unshared->figure, 'unit' => __($line->unshared->unit)]) . ' · ' . __($line->costsSaid)"
                :trailing="__('stacks.room.occupies', ['figure' => $line->occupies->figure, 'unit' => __($line->occupies->unit)])"
            />
        @empty
            <x-design::row :headline="__('stacks.room.nothing_accounted')" />
        @endforelse
    </x-design::section>

    {{-- Each completed download on its card, with where it stands and what
         removing it would cost. Nothing here is selected and nothing is
         proposed: stopping seeding is offered on each card alike, and opens on
         what it would cost rather than doing it. --}}
    <x-design::heading>{{ __('stacks.room.downloads') }}</x-design::heading>
    @forelse ($this->answer()->downloads as $download)
        <x-design::card>
            <x-design::strong>{{ $download->name }}</x-design::strong>
            <x-design::note>{{ __('stacks.room.takes', ['figure' => $download->size->figure, 'unit' => __($download->size->unit)]) }}</x-design::note>
            <x-design::body>{{ __($download->standingSaid) }}</x-design::body>

            @if ($download->ratioSaid !== '')
                <x-design::note>{{ __($download->ratioSaid, ['ratio' => $download->ratio]) }}</x-design::note>
                <x-operator::gloss :gloss="$this->gloss('ratio')" />
            @endif

            @if ($download->consequence !== '')
                <x-design::note>{{ $download->consequence }}</x-design::note>
            @endif

            <x-design::action
                label="{{ __('stacks.room.stop_seeding') }}"
                answers-to="{{ __('stacks.room.stop_seeding_that', ['download' => $download->name]) }}"
                :goes="$this->goes()->ofItself()->changing()->lettingGo($download->name)"
                tone="tonal"
            />
        </x-design::card>
    @empty
        <x-design::body>{{ __('stacks.room.no_downloads') }}</x-design::body>
    @endforelse

    <x-design::note>{{ __('stacks.room.at_the_machine') }}</x-design::note>

    <x-design::action label="{{ __('health.ask_again') }}" tap="again()" tone="tonal" />
</x-operator::content>
@else
    <x-operator::what-stopped-the-reading
        :went="$this->answer()->went"
        :sign-in-goes-to="$this->goes()->signIn()"
    />
@endif

<x-operator::screen-closes :goes="$this->goes()" :here="$this->itsTab()" />
