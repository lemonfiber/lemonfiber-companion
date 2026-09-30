<x-operator::stack-opens :title="$this->stack()->name()->shown()" :stacks="$this->stacksToChooseFrom()" :choosing="$this->choosingAStack" />

@if ($this->answer()->went->cameBack())
<x-operator::content>
@if ($this->answer()->refused !== null)
    {{-- The stack answered and could not read its own description: its words,
         and not a machine that is not answering. Asking again is answered
         the same way until what it names is put right, so it is not
         offered. --}}
    <x-operator::heading>{{ __('stacks.catalogue.refused') }}</x-operator::heading>
    <x-operator::refused-in-its-words :refused="$this->answer()->refused" />
    <native:text>{{ __('stacks.catalogue.same_answer') }}</native:text>
@else
    {{-- Said once, over the list: every sentence below is the stack's own
         description of itself, read with nothing started. --}}
    <x-operator::note>{{ __('stacks.catalogue.as_declared') }}</x-operator::note>

    @forelse ($this->answer()->services as $service)
        {{-- What it does for the house and what the house goes without lead,
             and the name follows: a name and a description alone say what a
             service is, and only what its absence costs says whether it
             matters. --}}
        <x-operator::entry>
            <x-operator::emphasis>{{ $service->describes }}</x-operator::emphasis>
            <native:text>{{ __('stacks.catalogue.without_it', ['without' => $service->withoutIt]) }}</native:text>
            <x-operator::note>{{ __($service->mattersSaid) }}</x-operator::note>
            <x-operator::note>{{ $service->name }}</x-operator::note>
        </x-operator::entry>
    @empty
        {{-- The stack answered and declares nothing. Not the same screen as a
             stack that could not be asked, which never reaches this branch. --}}
        <x-operator::emphasis>{{ __('stacks.catalogue.nothing_declared') }}</x-operator::emphasis>
    @endforelse

    <x-operator::heading>{{ __('stacks.catalogue.dropped') }}</x-operator::heading>

    @forelse ($this->answer()->dropped as $dropped)
        {{-- By the id it was declared under, which is the name somebody
             remembers, with why it went and what took its place. --}}
        <x-operator::entry>
            <x-operator::emphasis>{{ $dropped->id }}</x-operator::emphasis>
            <native:text>{{ __('stacks.catalogue.removed_in', ['version' => $dropped->removedIn, 'reason' => $dropped->reason]) }}</native:text>

            @if ($dropped->replacedBy !== '')
                <x-operator::note>{{ __('stacks.catalogue.replaced_by', ['by' => $dropped->replacedBy]) }}</x-operator::note>
            @else
                {{-- Nothing took its place, which is the commonest answer and
                     is said rather than left as a gap. --}}
                <x-operator::note>{{ __('stacks.catalogue.not_replaced') }}</x-operator::note>
            @endif
        </x-operator::entry>
    @empty
        <x-operator::note>{{ __('stacks.catalogue.nothing_dropped') }}</x-operator::note>
    @endforelse

    <x-operator::action label="{{ __('health.ask_again') }}" tap="again()" />
@endif
</x-operator::content>
@else
    <x-operator::what-stopped-the-reading
        :went="$this->answer()->went"
        :sign-in-goes-to="$this->goes()->signIn()"
    />
@endif

<x-operator::screen-closes :goes="$this->goes()" :here="$this->itsTab()" />
