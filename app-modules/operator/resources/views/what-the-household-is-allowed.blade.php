<x-wayfinding::stack-opens :title="$this->stack()->name()->shown()" :stacks="$this->stacksToChooseFrom()" :choosing="$this->choosingAStack" />

@if ($this->answer()->went->cameBack())
<x-operator::content>
    {{-- The core's own sentences, in its own order and wording: the
         household's rules are the core's to state. --}}
    <x-design::card>
        @forelse ($this->answer()->sentences as $sentence)
            <x-design::body>{{ $sentence }}</x-design::body>
        @empty
            {{-- Said in as many words: a blank card is what a stack that would
                 not say looks like, and this arm is only reached where it
                 answered. --}}
            <x-design::strong>{{ __('household.nothing_owed') }}</x-design::strong>
            <x-design::body>{{ __('household.nothing_owed_action') }}</x-design::body>
        @endforelse
    </x-design::card>

    <x-design::action label="{{ __('health.ask_again') }}" tap="again()" tone="tonal" />
</x-operator::content>
@else
    <x-operator::what-stopped-the-reading
        :settings-would-not-open="$this->theSettingsWouldNotOpen"
        :went="$this->answer()->went"
        :sign-in-goes-to="$this->goes()->signIn()"
    />
@endif

<x-operator::screen-closes :goes="$this->goes()" :here="$this->itsTab()" />
