<x-operator::screen-opens :title="$this->stack()->name()->shown()" />

@if ($this->answer()->went->cameBack())
<x-operator::content>
    <x-operator::emphasis>{{ __($this->answer()->standingSaid) }}</x-operator::emphasis>
    <native:text>{{ $this->answer()->meaning }}</native:text>

    {{-- Whether the door was worked out, chosen, or chosen and refused: a
         derived door is never drawn as somebody's decision. --}}
    <x-operator::note>{{ __($this->answer()->chosenSaid, ['named' => $this->answer()->named, 'because' => $this->answer()->refusal]) }}</x-operator::note>

    @if ($this->answer()->begins->service !== '')
        <x-operator::entry>
            <x-operator::emphasis>{{ $this->answer()->begins->service }}</x-operator::emphasis>
            <x-operator::note>{{ __($this->answer()->begins->facingSaid) }}</x-operator::note>
            {{-- The address exactly as the stack sent it; none is put
                 together here. --}}
            @if ($this->answer()->begins->url !== '')
                <native:text>{{ $this->answer()->begins->url }}</native:text>
            @else
                <x-operator::note>{{ __('stacks.front_door.no_address') }}</x-operator::note>
            @endif
            @if ($this->answer()->begins->caution !== '')
                <x-operator::note>{{ $this->answer()->begins->caution }}</x-operator::note>
            @endif
        </x-operator::entry>
    @endif

    {{-- Everything else the household can reach, each with what it is to
         them and why it is not the door. --}}
    <x-operator::emphasis>{{ __('stacks.front_door.beside') }}</x-operator::emphasis>
    @forelse ($this->answer()->beside as $service)
        <x-operator::entry>
            <x-operator::emphasis>{{ $service->service }}</x-operator::emphasis>
            <x-operator::note>{{ __($service->facingSaid) }}</x-operator::note>
            <native:text>{{ $service->because }}</native:text>
            @if ($service->url !== '')
                <native:text>{{ $service->url }}</native:text>
            @else
                <x-operator::note>{{ __('stacks.front_door.no_address') }}</x-operator::note>
            @endif
            @if ($service->caution !== '')
                <x-operator::note>{{ $service->caution }}</x-operator::note>
            @endif
        </x-operator::entry>
    @empty
        <x-operator::note>{{ __('stacks.front_door.nothing_beside') }}</x-operator::note>
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
