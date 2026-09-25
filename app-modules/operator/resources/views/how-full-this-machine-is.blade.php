<x-operator::screen-opens :title="$this->stack()->name()->shown()" />

@if ($this->answer()->went->cameBack())
<x-operator::content>
    {{-- Where the machine stands first: it stands where its worst volume
         does, and a volume nobody could read is never drawn as comfortable. --}}
    <x-operator::emphasis>{{ __($this->answer()->standsSaid) }}</x-operator::emphasis>

    @if ($this->answer()->halted)
        <x-operator::note>{{ __('stacks.room.halted') }}</x-operator::note>
    @endif

    @forelse ($this->answer()->volumes as $volume)
        <x-operator::entry>
            <native:text>{{ __($volume->holdsSaid) }}</native:text>

            @if ($volume->point !== '')
                <x-operator::note>{{ $volume->point }}</x-operator::note>
            @endif

            <x-operator::note>{{ __($volume->standsSaid) }}</x-operator::note>

            @if ($volume->free !== null)
                <x-operator::note>{{ __('stacks.room.free', ['figure' => $volume->free->figure, 'unit' => __($volume->free->unit)]) }}</x-operator::note>
            @else
                <x-operator::note>{{ __('stacks.room.free_unread') }}</x-operator::note>
            @endif

            @if ($volume->limit !== null)
                <x-operator::note>{{ __('stacks.room.limit', ['figure' => $volume->limit->figure, 'unit' => __($volume->limit->unit)]) }}</x-operator::note>
            @endif

            <x-operator::note>{{ __('stacks.room.committed', ['figure' => $volume->committed->figure, 'unit' => __($volume->committed->unit)]) }}</x-operator::note>

            @if ($volume->projected !== null)
                <x-operator::note>{{ __('stacks.room.projected', ['figure' => $volume->projected->figure, 'unit' => __($volume->projected->unit)]) }}</x-operator::note>
            @endif

            {{-- A network share answers with what it was last told, so its
                 figures are dated rather than presented as now. --}}
            @if ($volume->agoSaid !== '')
                <x-operator::note>{{ __('stacks.room.as_of', ['ago' => trans_choice($volume->agoSaid, $volume->agoCount)]) }}</x-operator::note>
            @endif
        </x-operator::entry>
    @empty
        <x-operator::note>{{ __('stacks.room.no_volumes') }}</x-operator::note>
    @endforelse

    {{-- Where the room went, by the categories the stack gives: never a
         listing of files. --}}
    <x-operator::emphasis>{{ __('stacks.room.account') }}</x-operator::emphasis>
    @forelse ($this->answer()->account as $line)
        <x-operator::entry>
            <native:text>{{ __($line->aboutSaid, ['tree' => $line->tree]) }}</native:text>
            <x-operator::note>{{ __('stacks.room.occupies', ['figure' => $line->occupies->figure, 'unit' => __($line->occupies->unit)]) }}</x-operator::note>

            @if ($line->unshared !== null)
                <x-operator::note>{{ __('stacks.room.unshared', ['figure' => $line->unshared->figure, 'unit' => __($line->unshared->unit)]) }}</x-operator::note>
            @endif

            <x-operator::note>{{ __($line->costsSaid) }}</x-operator::note>
        </x-operator::entry>
    @empty
        <x-operator::note>{{ __('stacks.room.nothing_accounted') }}</x-operator::note>
    @endforelse

    {{-- Each completed download with where it stands and, on the same row,
         what removing it would cost. Nothing here is selected. --}}
    <x-operator::emphasis>{{ __('stacks.room.downloads') }}</x-operator::emphasis>
    @forelse ($this->answer()->downloads as $download)
        <x-operator::entry>
            <native:text>{{ $download->name }}</native:text>
            <x-operator::note>{{ __('stacks.room.takes', ['figure' => $download->size->figure, 'unit' => __($download->size->unit)]) }}</x-operator::note>
            <x-operator::note>{{ __($download->standingSaid) }}</x-operator::note>

            @if ($download->ratioSaid !== '')
                <x-operator::note>{{ __($download->ratioSaid, ['ratio' => $download->ratio]) }}</x-operator::note>
                <x-operator::gloss :gloss="$this->gloss('ratio')" />
            @endif

            @if ($download->consequence !== '')
                <x-operator::note>{{ $download->consequence }}</x-operator::note>
            @endif
        </x-operator::entry>
    @empty
        <x-operator::note>{{ __('stacks.room.no_downloads') }}</x-operator::note>
    @endforelse

    <x-operator::note>{{ __('stacks.room.at_the_machine') }}</x-operator::note>

    <x-operator::action label="{{ __('health.ask_again') }}" tap="again()" />
</x-operator::content>
@else
    <x-operator::what-stopped-the-reading
        :went="$this->answer()->went"
        :sign-in-goes-to="$this->goes()->signIn()"
    />
@endif

<x-operator::screen-closes :goes="$this->goes()" here="health" />
