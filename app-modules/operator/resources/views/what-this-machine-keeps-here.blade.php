<x-operator::screen-opens :title="$this->stack()->name()->shown()" />

@if ($this->answer()->went->cameBack())
<x-operator::content>
    <x-operator::emphasis>{{ __('stacks.keeps.roots') }}</x-operator::emphasis>
    @forelse ($this->answer()->roots as $root)
        <x-operator::entry>
            <native:text>{{ $root->what }}</native:text>
            <x-operator::note>{{ $root->where }}</x-operator::note>
        </x-operator::entry>
    @empty
        <x-operator::note>{{ __('stacks.keeps.no_roots') }}</x-operator::note>
    @endforelse

    <x-operator::emphasis>{{ __('stacks.keeps.kept') }}</x-operator::emphasis>
    @forelse ($this->answer()->kept as $one)
        {{-- Whether it holds a secret is on every row, not only on the rows
             that do: a label shown only sometimes cannot be told apart from
             one somebody forgot. The row has no field for a value. --}}
        <x-operator::entry>
            <native:text>{{ $one->what }}</native:text>
            <x-operator::note>{{ $one->where }}</x-operator::note>
            <x-operator::note>{{ $one->why }}</x-operator::note>
            <x-operator::note>{{ __($one->secretSaid) }}</x-operator::note>
        </x-operator::entry>
    @empty
        <x-operator::note>{{ __('stacks.keeps.nothing_kept') }}</x-operator::note>
    @endforelse

    <x-operator::emphasis>{{ __('stacks.keeps.beside') }}</x-operator::emphasis>
    @forelse ($this->answer()->beside as $one)
        <x-operator::entry>
            <native:text>{{ $one->what }}</native:text>
            <x-operator::note>{{ $one->why }}</x-operator::note>
        </x-operator::entry>
    @empty
        <x-operator::note>{{ __('stacks.keeps.nothing_beside') }}</x-operator::note>
    @endforelse

    <x-operator::emphasis>{{ __('stacks.keeps.copies') }}</x-operator::emphasis>
    @if ($this->answer()->copies->went->cameBack())
        @forelse ($this->answer()->copies->names as $name)
            {{-- Putting a copy back is offered for a copy the stack listed
                 and for nothing else, and it opens on what putting it back
                 would do rather than doing it. --}}
            <x-operator::entry>
                <x-operator::note>{{ $name }}</x-operator::note>
                <x-operator::action
                    label="{{ __('stacks.keeps.put_back') }}"
                    answers-to="{{ __('stacks.keeps.put_back_that', ['copy' => $name]) }}"
                    :goes="$this->goes()->ofItself()->puttingBack($name)"
                />
            </x-operator::entry>
        @empty
            <x-operator::note>{{ __('stacks.keeps.no_copies') }}</x-operator::note>
        @endforelse
    @else
        {{-- A list that could not be read has its own sentence. Drawn as
             an empty list, it would say no copy had ever been taken. --}}
        <x-operator::note>{{ __('stacks.keeps.copies_unread') }}</x-operator::note>
        {{-- What stood in the way, and the remedy, both from the obstacle.
             Drawn here, not by the shared component, because that one brings
             its own ask-again and this screen already has one. --}}
        @if ($this->answer()->copies->went->isSignedIn)
            <x-operator::note>{{ __($this->answer()->copies->went->met) }}</x-operator::note>
            <x-operator::note>{{ __($this->answer()->copies->went->remedy) }}</x-operator::note>
        @else
            <x-operator::note>{{ __('connection.session_has_ended') }}</x-operator::note>
            <x-operator::action label="{{ __('connection.sign_in') }}" :goes="$this->goes()->signIn()" />
        @endif
    @endif

    <x-operator::action label="{{ __('stacks.keeps.take_a_copy') }}" :goes="$this->goes()->ofItself()->copy()" />

    <x-operator::action label="{{ __('health.ask_again') }}" tap="again()" />
</x-operator::content>
@else
    <x-operator::what-stopped-the-reading
        :went="$this->answer()->went"
        :sign-in-goes-to="$this->goes()->signIn()"
    />
@endif

<x-operator::screen-closes :goes="$this->goes()" here="health" />
