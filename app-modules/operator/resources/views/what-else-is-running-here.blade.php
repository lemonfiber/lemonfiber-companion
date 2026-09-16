<x-operator::screen-opens :title="$this->stack()->name()->shown()" />

@if ($this->answer()->went->cameBack())
<native:column class="w-full gap-4 px-6 py-4">
    {{-- How many, said before the list. An operator who opened this because
         something looked unfamiliar wants the count before the rows. --}}
    <x-operator::emphasis>
        {{ trans_choice('health.undeclared_count', $this->howMany()) }}
    </x-operator::emphasis>

    {{-- N2-R21: what these are, said once and above them. The sentence is
         the whole point of the screen — a list of names an operator does
         not recognise, with nothing saying why they are here, is what this
         replaces. --}}
    <x-operator::note>{{ __('health.undeclared_explained') }}</x-operator::note>

    @forelse ($this->answer()->running as $container)
        <x-operator::entry>
            {{-- N2-R21 asks for each to be named and for what it is running
                 to be stated, and for no verb against it. There is no
                 button on this row and no identifier on the value that a
                 verb would accept. --}}
            <x-operator::emphasis>{{ $container->named }}</x-operator::emphasis>
            <x-operator::note>{{ $container->describes }}</x-operator::note>
            <x-operator::note>{{ __($container->runs) }}</x-operator::note>
        </x-operator::entry>
    @empty
        {{-- Not the same screen as a machine that could not be asked.
             Nothing unaccounted for is the answer the operator wants, and
             saying so is what tells it apart from the obstacle branch. --}}
        <x-operator::emphasis>{{ __('health.nothing_undeclared') }}</x-operator::emphasis>
        <native:text>{{ __('health.nothing_undeclared_action') }}</native:text>
    @endforelse
</native:column>
@else
    <x-operator::what-stopped-the-reading
        :went="$this->answer()->went"
        :sign-in-goes-to="$this->goes()->signIn()"
    />
@endif

<x-operator::screen-closes :goes="$this->goes()" here="health" />
