@use('Modules\Stacks\Api\AStacksScreen')
<x-wayfinding::stack-opens :back="$this->hasAWayBack()" :title="$this->stack()->name()->shown()" :stacks="$this->stacksToChooseFrom()" :choosing="$this->choosingAStack" />

@if ($this->answer()->went->cameBack())
<x-operator::content>
    <x-design::title>{{ __('stacks.origins.road_in') }}</x-design::title>

    {{-- Said once, over the list: every word below is what this machine
         declares, and none was looked up. A project that has gone away
         changes nothing here, which is worth knowing before somebody reads a
         licence as having been checked against it today. --}}
    <x-design::note>{{ __('stacks.origins.as_declared') }}</x-design::note>

    @forelse ($this->answer()->services as $service)
        {{-- A card per service, in the order the stack declares them. The
             licence is on every card rather than where it is unusual: a
             licence shown only sometimes is one nobody can tell was checked. --}}
        <x-design::card>
            <x-design::strong>{{ $service->name }}</x-design::strong>
            <x-design::body>{{ __('stacks.origins.runs', ['image' => $service->image, 'pinned' => $service->pinned]) }}</x-design::body>
            {{-- The digest beside the version rather than in its place: the
                 version is what a person recognises, and the digest is what
                 makes the pin immutable. An image pinned by tag alone says so,
                 because a card with no digest and nothing said reads the same
                 as one this screen forgot to draw. --}}
            @if ($service->digest !== '')
                <x-design::note>{{ __('stacks.origins.digest', ['digest' => $service->digest]) }}</x-design::note>
            @else
                <x-design::note>{{ __('stacks.origins.no_digest') }}</x-design::note>
            @endif
            <x-design::body>{{ __('stacks.origins.licence', ['licence' => $service->licence]) }}</x-design::body>
            <x-design::note>{{ __('stacks.origins.upstream', ['upstream' => $service->upstream]) }}</x-design::note>
        </x-design::card>
    @empty
        {{-- The stack answered and declares nothing. Not the same screen as a
             stack that could not be asked, which never reaches this branch. --}}
        <x-design::body>{{ __('stacks.origins.nothing_declared') }}</x-design::body>
    @endforelse

    <x-design::action label="{{ __('health.ask_again') }}" tap="askAgain()" tone="tonal" />
</x-operator::content>
@else
    <x-operator::what-stopped-the-reading
        :settings-would-not-open="$this->theSettingsWouldNotOpen"
        :went="$this->answer()->went"
        :goes="$this->goes()"
    />
@endif

<x-operator::screen-closes :goes="$this->goes()" :here="$this->itsTab()" :marks="$this->marks()" />
