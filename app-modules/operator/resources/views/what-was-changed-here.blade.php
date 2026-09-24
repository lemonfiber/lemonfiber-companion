<x-operator::screen-opens :title="$this->stack()->name()->shown()" />

@if ($this->answer()->went->cameBack())
<x-operator::content>
    @forelse ($this->answer()->moments as $moment)
        {{-- One *when* over every change made then. Changes made at one
             moment are drawn under it together rather than each with a time,
             where the one above would read as the later. --}}
        <x-operator::emphasis>{{ trans_choice($moment->whenSaid, $moment->whenCount) }}</x-operator::emphasis>

        @forelse ($moment->changes as $change)
            {{-- An entry per change, never a line of a log: what it did is
                 the heading, and what did it, how far it goes back and how
                 many came with it are the facts an operator decides from. --}}
            <x-operator::entry>
                <x-operator::emphasis>{{ $change->did }}</x-operator::emphasis>
                <x-operator::note>{{ __('stacks.record.by', ['operation' => $change->operation, 'target' => $change->target]) }}</x-operator::note>
                <native:text>{{ __($change->reversalSaid) }}</native:text>

                {{-- Always said, including *on its own*: undoing one line of
                     an operation that made more leaves a machine in a state
                     nobody chose, so the count is part of every row. --}}
                <x-operator::note>{{ trans_choice('stacks.record.alongside', $change->alongside) }}</x-operator::note>

                @if ($change->because !== '')
                    {{-- Why putting it back stops short. Not why it was made:
                         the stack says the first and never the second. --}}
                    <x-operator::note>{{ __('stacks.record.stops_short', ['because' => $change->because]) }}</x-operator::note>
                @endif

                @if ($change->instead !== '')
                    <x-operator::note>{{ __('stacks.record.instead', ['instead' => $change->instead]) }}</x-operator::note>
                @endif
            </x-operator::entry>
        @empty
            {{-- Unreachable while `HowTheRecordReads` opens a moment only for
                 a change made at it, and written anyway: `F6` wants the empty
                 case to be the same edit as the loop, so a moment that lost
                 its changes says so rather than leaving a *when* over a blank. --}}
            <x-operator::note>{{ __('stacks.record.nothing_changed') }}</x-operator::note>
        @endforelse
    @empty
        {{-- The stack answered and has changed nothing within the horizon
             below. Not the same screen as a stack that could not be asked,
             which never reaches this branch. --}}
        <x-operator::emphasis>{{ __('stacks.record.nothing_changed') }}</x-operator::emphasis>
    @endforelse

    {{-- Where the list ends, which is where somebody scrolling reaches it:
         *nothing before this* and *nothing happened before this* are
         opposite claims, and without the edge said here the last entry reads
         as the machine's first day. Said under an empty record too, which is
         empty within this and no further. The sentence is the stack's; the
         lead-in is ours. --}}
    <x-operator::note>{{ __('stacks.record.horizon', ['horizon' => $this->answer()->horizon]) }}</x-operator::note>

    {{-- On the answered arm too: somebody who has just changed something at
         the machine is looking at a screen they want to ask again. --}}
    <x-operator::action label="{{ __('health.ask_again') }}" tap="again()" />
</x-operator::content>
@else
    <x-operator::what-stopped-the-reading
        :went="$this->answer()->went"
        :sign-in-goes-to="$this->goes()->signIn()"
    />
@endif

<x-operator::screen-closes :goes="$this->goes()" here="health" />
