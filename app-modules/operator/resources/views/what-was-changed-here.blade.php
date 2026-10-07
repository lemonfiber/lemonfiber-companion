@use('Modules\Stacks\Api\AStacksScreen')
<x-wayfinding::stack-opens :title="$this->stack()->name()->shown()" :stacks="$this->stacksToChooseFrom()" :choosing="$this->choosingAStack" />

@if ($this->answer()->went->cameBack())
<x-operator::content>
    @forelse ($this->answer()->moments as $moment)
        {{-- One *when* over every change made then. Changes made at one
             moment are drawn under it together rather than each with a time,
             where the one above would read as the later. --}}
        <x-design::heading>{{ trans_choice($moment->whenSaid, $moment->whenCount) }}</x-design::heading>

        @forelse ($moment->changes as $change)
            {{-- A card per change, never a line of a log: what it did leads,
                 and what did it, how far it goes back and how many came with
                 it are the facts an operator decides from. --}}
            <x-design::card>
                <x-design::strong>{{ $change->did }}</x-design::strong>
                <x-design::note>{{ __('stacks.record.by', ['operation' => $change->operation, 'target' => $change->target]) }}</x-design::note>
                <x-design::body>{{ __($change->reversalSaid) }}</x-design::body>

                {{-- Always said, including *on its own*: undoing one line of
                     an operation that made more leaves a machine in a state
                     nobody chose, so the count is on every card. --}}
                <x-design::note>{{ trans_choice('stacks.record.alongside', $change->alongside) }}</x-design::note>

                @if ($change->because !== '')
                    {{-- Why putting it back stops short. Not why it was made:
                         the stack says the first and never the second. --}}
                    <x-design::note>{{ __('stacks.record.stops_short', ['because' => $change->because]) }}</x-design::note>
                @endif

                @if ($change->instead !== '')
                    <x-design::note>{{ __('stacks.record.instead', ['instead' => $change->instead]) }}</x-design::note>
                @endif
            </x-design::card>
        @empty
            {{-- Unreachable while `HowTheRecordReads` opens a moment only for
                 a change made at it, and written anyway: the empty case is the
                 same edit as the loop, so a moment that lost its changes says
                 so rather than leaving a *when* over a blank. --}}
            <x-design::note>{{ __('stacks.record.nothing_changed') }}</x-design::note>
        @endforelse

        {{-- The way to putting back what was done then. It leads to a screen
             that says what goes with it before anything is agreed to; nothing
             is put back from here. --}}
        <x-design::action
            label="{{ __('stacks.record.put_back') }}"
            answers-to="{{ __('stacks.record.put_back_that', ['did' => $moment->leadsWith, 'when' => trans_choice($moment->whenSaid, $moment->whenCount)]) }}"
            :goes="$this->goes()->puttingARunBack($moment->stamp)"
        />
    @empty
        {{-- The stack answered and has changed nothing within the horizon
             below. Not the same screen as a stack that could not be asked,
             which never reaches this branch. --}}
        <x-design::body>{{ __('stacks.record.nothing_changed') }}</x-design::body>
    @endforelse

    {{-- Where the list ends, which is where somebody scrolling reaches it:
         *nothing before this* and *nothing happened before this* are
         opposite claims, and without the edge said here the last card reads
         as the machine's first day. Said under an empty record too, which is
         empty within this and no further. The sentence is the stack's; the
         lead-in is ours. --}}
    <x-design::note>{{ __('stacks.record.horizon', ['horizon' => $this->answer()->horizon]) }}</x-design::note>

    {{-- On the answered arm too: somebody who has just changed something at
         the machine is looking at a screen they want to ask again. --}}
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
