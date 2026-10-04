<x-wayfinding::stack-opens :title="$this->stack()->name()->shown()" :stacks="$this->stacksToChooseFrom()" :choosing="$this->choosingAStack" />

@if ($this->answer()->went->cameBack())
<x-operator::content>
    {{-- The preset first, with what it means beside it: a preset's name alone
         is a word somebody has to look up before they know whether they will
         be woken at three. --}}
    <x-design::heading>{{ __('stacks.alerts.preset', ['preset' => $this->answer()->preset]) }}</x-design::heading>
    <x-design::body>{{ $this->answer()->means }}</x-design::body>

    {{-- Each one heard or kept quiet whatever the preset says, which is the
         operator's own decision and said as theirs. --}}
    <x-design::section :label="__('stacks.alerts.set_apart')">
        @forelse ($this->answer()->exceptions as $event)
            <x-design::row :headline="$event->kind" :supporting="__($event->heardSaid)" />
        @empty
            <x-design::row :headline="__('stacks.alerts.nothing_set_apart')" />
        @endforelse
    </x-design::section>

    {{-- Where it is changed, said once: this screen shows the setting and
         changes nothing, and raises nothing of its own. --}}
    <x-design::note>{{ __('stacks.alerts.changed_at_the_machine') }}</x-design::note>

    <x-design::action label="{{ __('health.ask_again') }}" tap="again()" tone="tonal" />
</x-operator::content>
@else
    <x-operator::what-stopped-the-reading
        :settings-would-not-open="$this->theSettingsWouldNotOpen"
        :went="$this->answer()->went"
        :sign-in-goes-to="$this->goes()->signIn()"
    />
@endif

<x-operator::screen-closes :goes="$this->goes()" :here="$this->itsTab()" :marks="$this->marks()" />
