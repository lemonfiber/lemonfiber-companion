<x-operator::screen-opens :title="$this->stack()->name()->shown()" />

@if ($this->answer()->went->cameBack())
<x-operator::content>
    <x-operator::heading>{{ __('stacks.already_here.found') }}</x-operator::heading>

    {{-- Every project and service first, before any mode: a mode chosen
         before this is read is chosen blind. --}}
    @if ($this->answer()->looked)
        @forelse ($this->answer()->projects as $project)
            <x-operator::emphasis>{{ __('stacks.already_here.project', ['project' => $project->project]) }}</x-operator::emphasis>
            @forelse ($project->services as $service)
                <x-operator::entry>
                    <x-operator::emphasis>{{ $service->service }}</x-operator::emphasis>
                    <native:text>{{ __($service->runningSaid) }}</native:text>
                    <native:text>{{ __($service->adoptableSaid) }}</native:text>
                    @if ($service->ports !== '')
                        <x-operator::note>{{ __('stacks.already_here.ports', ['ports' => $service->ports]) }}</x-operator::note>
                    @else
                        <x-operator::note>{{ __('stacks.already_here.no_ports') }}</x-operator::note>
                    @endif
                </x-operator::entry>
            @empty
                <x-operator::note>{{ __('stacks.already_here.no_services') }}</x-operator::note>
            @endforelse
        @empty
            <x-operator::note>{{ __('stacks.already_here.nothing_found') }}</x-operator::note>
        @endforelse

        {{-- Each port in the way names what already holds it. --}}
        <x-operator::heading>{{ __('stacks.already_here.conflicts') }}</x-operator::heading>
        @forelse ($this->answer()->conflicts as $line)
            <native:text>{{ __($line->said, $line->with) }}</native:text>
        @empty
            <x-operator::note>{{ __('stacks.already_here.no_conflicts') }}</x-operator::note>
        @endforelse

        {{-- Where each service would be reached instead, drawn on every
             reading for as long as the stack reports it. --}}
        <x-operator::heading>{{ __('stacks.already_here.beside') }}</x-operator::heading>
        @forelse ($this->answer()->beside as $line)
            <native:text>{{ __($line->said, $line->with) }}</native:text>
        @empty
            <x-operator::note>{{ __('stacks.already_here.none_moved') }}</x-operator::note>
        @endforelse

        <x-operator::heading>{{ __('stacks.already_here.cannot_take') }}</x-operator::heading>
        @forelse ($this->answer()->unsupported as $line)
            <native:text>{{ __($line->said, $line->with) }}</native:text>
        @empty
            <x-operator::note>{{ __('stacks.already_here.nothing_unsupported') }}</x-operator::note>
        @endforelse
    @else
        {{-- A survey that could not look is never drawn as a machine with
             nothing on it, and says nothing about what is in the way. --}}
        <x-operator::emphasis>{{ __('stacks.already_here.could_not_look') }}</x-operator::emphasis>
    @endif

    {{-- The remedy is words and nothing more: no act the stack offers carries
         it out, and it is the operator's to take on their own disks. --}}
    @if ($this->answer()->linking->because !== '')
        <x-operator::heading>{{ __('stacks.already_here.cannot_link') }}</x-operator::heading>
        <native:text>{{ $this->answer()->linking->because }}</native:text>
        <native:text>{{ $this->answer()->linking->cost }}</native:text>
        @if ($this->answer()->linking->filesystems !== '')
            <x-operator::note>{{ __('stacks.already_here.filesystems', ['filesystems' => $this->answer()->linking->filesystems]) }}</x-operator::note>
        @endif
        <native:text>{{ $this->answer()->linking->remedy }}</native:text>
        <x-operator::note>{{ __('stacks.already_here.remedy_is_yours') }}</x-operator::note>
    @endif

    {{-- The modes last, in the stack's order, each with whether it disturbs
         what is running. Chosen already only where the survey says so. --}}
    <x-operator::heading>{{ __('stacks.already_here.modes') }}</x-operator::heading>
    @forelse ($this->answer()->modes as $mode)
        <x-operator::entry>
            <x-operator::emphasis>{{ $mode->mode }}</x-operator::emphasis>
            <native:text>{{ $mode->what }}</native:text>
            <x-operator::note>{{ __($mode->disturbsSaid) }}</x-operator::note>
            @if ($mode->preselected)
                <x-operator::note>{{ __('stacks.already_here.preselected') }}</x-operator::note>
            @endif
        </x-operator::entry>
    @empty
        <x-operator::note>{{ __('stacks.already_here.no_modes') }}</x-operator::note>
    @endforelse

    <x-operator::action label="{{ __('health.ask_again') }}" tap="again()" />
</x-operator::content>
@else
    <x-operator::what-stopped-the-reading
        :went="$this->answer()->went"
        :sign-in-goes-to="$this->goes()->signIn()"
    />
@endif

<x-operator::screen-closes :goes="$this->goes()" here="health" />
