<x-operator::screen-opens :title="$this->stack()->name()->shown()" />

@if ($this->answer()->went->cameBack())
<x-operator::content>
    <x-operator::emphasis>{{ __('stacks.itself.running', ['version' => $this->answer()->running]) }}</x-operator::emphasis>

    {{-- How it was installed decides who can replace it, so it comes before
         whether there is anything newer. --}}
    <native:text>{{ __($this->answer()->installedSaid) }}</native:text>
    @if ($this->answer()->owner !== '')
        <x-operator::note>{{ __('stacks.itself.owner', ['owner' => $this->answer()->owner]) }}</x-operator::note>
    @endif

    <x-operator::emphasis>{{ __($this->answer()->standsSaid) }}</x-operator::emphasis>
    @if ($this->answer()->offered !== '')
        <x-operator::note>{{ __('stacks.itself.offered', ['version' => $this->answer()->offered]) }}</x-operator::note>
    @endif
    @if ($this->answer()->changed !== '')
        <native:text>{{ $this->answer()->changed }}</native:text>
    @endif
    @if ($this->answer()->untold !== '')
        <x-operator::note>{{ $this->answer()->untold }}</x-operator::note>
    @endif

    {{-- What updating would bring and leave behind, before anybody runs it. --}}
    <x-operator::note>{{ $this->answer()->carries }}</x-operator::note>
    <x-operator::note>{{ $this->answer()->afterwards }}</x-operator::note>

    {{-- The command is shown to be run at the machine; nothing here runs it. --}}
    @if ($this->answer()->command !== '')
        <x-operator::note>{{ __('stacks.itself.run_at_the_machine') }}</x-operator::note>
        <native:text>{{ $this->answer()->command }}</native:text>
    @elseif ($this->answer()->instead !== '')
        <x-operator::note>{{ $this->answer()->instead }}</x-operator::note>
    @endif

    <x-operator::note>{{ __('stacks.itself.not_the_services') }}</x-operator::note>

    <x-operator::action label="{{ __('health.ask_again') }}" tap="again()" />
</x-operator::content>
@else
    <x-operator::what-stopped-the-reading
        :went="$this->answer()->went"
        :sign-in-goes-to="$this->goes()->signIn()"
    />
@endif

<x-operator::screen-closes :goes="$this->goes()" here="health" />
