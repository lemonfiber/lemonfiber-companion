<x-operator::screen-opens :title="$this->stack()->name()->shown()" />

@if ($this->answer()->went->cameBack())
<x-operator::content>
    {{-- Said once, over the list: every word below is what this machine
         declares, and none was looked up. A project that has gone away
         changes nothing here, which is worth knowing before somebody reads a
         licence as having been checked against it today. --}}
    <x-operator::note>{{ __('stacks.origins.as_declared') }}</x-operator::note>

    @forelse ($this->answer()->services as $service)
        {{-- An entry per service, in the order the stack declares them. The
             licence is on every row rather than where it is unusual: a licence
             shown only sometimes is one nobody can tell was checked. --}}
        <x-operator::entry>
            <x-operator::emphasis>{{ $service->name }}</x-operator::emphasis>
            <x-operator::note>{{ __('stacks.origins.runs', ['image' => $service->image, 'pinned' => $service->pinned]) }}</x-operator::note>
            <native:text>{{ __('stacks.origins.licence', ['licence' => $service->licence]) }}</native:text>
            <x-operator::note>{{ __('stacks.origins.upstream', ['upstream' => $service->upstream]) }}</x-operator::note>
        </x-operator::entry>
    @empty
        {{-- The stack answered and declares nothing. Not the same screen as a
             stack that could not be asked, which never reaches this branch. --}}
        <x-operator::emphasis>{{ __('stacks.origins.nothing_declared') }}</x-operator::emphasis>
    @endforelse

    <x-operator::action label="{{ __('health.ask_again') }}" tap="again()" />
</x-operator::content>
@else
    <x-operator::what-stopped-the-reading
        :went="$this->answer()->went"
        :sign-in-goes-to="$this->goes()->signIn()"
    />
@endif

<x-operator::screen-closes :goes="$this->goes()" here="health" />
