<x-operator::screen-opens :title="$this->stack()->name()->shown()" />

@if ($this->answer()->went->cameBack())
<x-operator::content>
    {{-- The preset first, with what it means beside it: a preset's name alone
         is a word somebody has to look up before they know whether they will
         be woken at three. --}}
    <x-operator::emphasis>{{ __('stacks.alerts.preset', ['preset' => $this->answer()->preset]) }}</x-operator::emphasis>
    <native:text>{{ $this->answer()->means }}</native:text>

    <x-operator::emphasis>{{ __('stacks.alerts.set_apart') }}</x-operator::emphasis>

    @forelse ($this->answer()->exceptions as $event)
        {{-- Each one heard or kept quiet whatever the preset says, which is
             the operator's own decision and said as theirs. --}}
        <x-operator::entry>
            <x-operator::emphasis>{{ $event->kind }}</x-operator::emphasis>
            <native:text>{{ __($event->heardSaid) }}</native:text>
        </x-operator::entry>
    @empty
        <x-operator::note>{{ __('stacks.alerts.nothing_set_apart') }}</x-operator::note>
    @endforelse

    {{-- Where it is changed, said once: this screen shows the setting and
         changes nothing, and raises nothing of its own. --}}
    <x-operator::note>{{ __('stacks.alerts.changed_at_the_machine') }}</x-operator::note>

    <x-operator::action label="{{ __('health.ask_again') }}" tap="again()" />
</x-operator::content>
@else
    <x-operator::what-stopped-the-reading
        :went="$this->answer()->went"
        :sign-in-goes-to="$this->goes()->signIn()"
    />
@endif

<x-operator::screen-closes :goes="$this->goes()" here="health" />
