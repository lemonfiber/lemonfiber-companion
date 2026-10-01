<x-operator::stack-opens :title="$this->stack()->name()->shown()" :stacks="$this->stacksToChooseFrom()" :choosing="$this->choosingAStack" />

@if ($this->answer()->went->cameBack())
<x-operator::content>
    <x-design::heading>{{ __('stacks.itself.running', ['version' => $this->answer()->running]) }}</x-design::heading>

    {{-- How it was installed decides who can replace it, so it comes before
         whether there is anything newer. --}}
    <x-design::body>{{ __($this->answer()->installedSaid) }}</x-design::body>
    @if ($this->answer()->owner !== '')
        <x-design::note>{{ __('stacks.itself.owner', ['owner' => $this->answer()->owner]) }}</x-design::note>
    @endif

    {{-- Where it stands against what has been released. A newer release is
         the heading, named; otherwise the standing is, with the newest
         version under it where there is one. A check that failed is never
         drawn as current. --}}
    @if ($this->answer()->out !== '')
        <x-design::standing
            :said="__('stacks.itself.is_out', ['version' => $this->answer()->out])"
            :tone="$this->answer()->tone"
        />
    @else
        <x-design::standing
            :said="__($this->answer()->standsSaid)"
            :tone="$this->answer()->tone"
            :note="$this->answer()->offered === '' ? '' : __('stacks.itself.offered', ['version' => $this->answer()->offered])"
        />
    @endif
    @if ($this->answer()->untold !== '')
        <x-design::note>{{ $this->answer()->untold }}</x-design::note>
    @endif

    {{-- What updating would bring and leave behind, before anybody runs it,
         and the command that does it, shown to be run at the machine: nothing
         here runs it. --}}
    <x-design::card>
        <x-design::body>{{ $this->answer()->carries }}</x-design::body>
        <x-design::note>{{ $this->answer()->afterwards }}</x-design::note>

        @if ($this->answer()->command !== '')
            <x-design::note>{{ __('stacks.itself.run_at_the_machine') }}</x-design::note>
            <x-design::verbatim>{{ $this->answer()->command }}</x-design::verbatim>
        @elseif ($this->answer()->instead !== '')
            <x-design::note>{{ $this->answer()->instead }}</x-design::note>
        @endif
    </x-design::card>

    <x-design::note>{{ __('stacks.itself.not_the_services') }}</x-design::note>

    {{-- The versions under this one, and what the running release changed. --}}
    <x-design::link label="{{ __('stacks.versions.road_in') }}" :goes="$this->goes()->ofItself()->versions()" />

    <x-design::action label="{{ __('health.ask_again') }}" tap="again()" tone="tonal" />
</x-operator::content>
@else
    <x-operator::what-stopped-the-reading
        :went="$this->answer()->went"
        :sign-in-goes-to="$this->goes()->signIn()"
    />
@endif

<x-operator::screen-closes :goes="$this->goes()" :here="$this->itsTab()" />
