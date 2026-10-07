@use('Modules\Stacks\Api\AStacksScreen')
<x-wayfinding::stack-opens :title="$this->stack()->name()->shown()" :stacks="$this->stacksToChooseFrom()" :choosing="$this->choosingAStack" />

@if ($this->answer()->went->cameBack())
<x-operator::content>
    {{-- How many, said before the list. An operator who opened this because
         something looked unfamiliar wants the count before the rows. --}}
    <x-design::title>{{ trans_choice('health.undeclared_count', $this->howMany()) }}</x-design::title>

    {{-- What these are, said once and above them. The sentence is
         the whole point of the screen — a list of names an operator does
         not recognise, with nothing saying why they are here, is what this
         replaces. --}}
    <x-design::note>{{ __('health.undeclared_explained') }}</x-design::note>

    <x-design::section>
        @forelse ($this->answer()->running as $container)
            {{-- Each is named, with what it does and what it is running, its
                 state's glyph at its start, and no verb against it. The row
                 neither goes nor taps, and the value carries no identifier a
                 verb would accept. --}}
            <x-design::row
                :headline="$container->named"
                :supporting="$container->describes"
                :trailing="__($container->runs)"
                :tone="$container->tone"
            />
        @empty
            {{-- Not the same screen as a machine that could not be asked.
                 Nothing unaccounted for is the answer the operator wants, and
                 saying so is what tells it apart from the obstacle branch. --}}
            <x-design::row
                :headline="__('health.nothing_undeclared')"
                :supporting="__('health.nothing_undeclared_action')"
            />
        @endforelse
    </x-design::section>

    {{-- A screen an operator cannot ask again is a screen that relies on
         being left and returned to. On both arms, and last, under what it is
         about, for the health screen's reason: somebody who has just changed
         something scrolls to the end of what they were reading, and that is
         where they want to ask whether it took. --}}
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
