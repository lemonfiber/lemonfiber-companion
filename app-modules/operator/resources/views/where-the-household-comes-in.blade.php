<x-operator::screen-opens :title="$this->stack()->name()->shown()" />

@if ($this->answer()->went->cameBack())
<x-operator::content>
    <x-design::standing :said="__($this->answer()->standingSaid)" :tone="$this->answer()->tone" />
    <x-design::body>{{ $this->answer()->meaning }}</x-design::body>

    {{-- Whether the door was worked out, chosen, or chosen and refused: a
         derived door is never drawn as somebody's decision. --}}
    <x-design::note>{{ __($this->answer()->chosenSaid, ['named' => $this->answer()->named, 'because' => $this->answer()->refusal]) }}</x-design::note>

    @if ($this->answer()->begins->service !== '')
        <x-design::card>
            <x-design::strong>{{ $this->answer()->begins->service }}</x-design::strong>
            <x-design::note>{{ __($this->answer()->begins->facingSaid) }}</x-design::note>
            {{-- The address exactly as the stack sent it; none is put
                 together here. --}}
            @if ($this->answer()->begins->url !== '')
                <x-design::verbatim>{{ $this->answer()->begins->url }}</x-design::verbatim>
            @else
                <x-design::note>{{ __('stacks.front_door.no_address') }}</x-design::note>
            @endif
            @if ($this->answer()->begins->caution !== '')
                <x-design::note>{{ $this->answer()->begins->caution }}</x-design::note>
            @endif
        </x-design::card>
    @endif

    {{-- Everything else the household can reach, each with what it is to
         them and why it is not the door. --}}
    <x-design::heading>{{ __('stacks.front_door.beside') }}</x-design::heading>
    @forelse ($this->answer()->beside as $service)
        <x-design::card>
            <x-design::strong>{{ $service->service }}</x-design::strong>
            <x-design::note>{{ __($service->facingSaid) }}</x-design::note>
            <x-design::body>{{ $service->because }}</x-design::body>
            @if ($service->url !== '')
                <x-design::verbatim>{{ $service->url }}</x-design::verbatim>
            @else
                <x-design::note>{{ __('stacks.front_door.no_address') }}</x-design::note>
            @endif
            @if ($service->caution !== '')
                <x-design::note>{{ $service->caution }}</x-design::note>
            @endif
        </x-design::card>
    @empty
        <x-design::body>{{ __('stacks.front_door.nothing_beside') }}</x-design::body>
    @endforelse

    <x-design::action label="{{ __('health.ask_again') }}" tap="again()" tone="tonal" />
</x-operator::content>
@else
    <x-operator::what-stopped-the-reading
        :went="$this->answer()->went"
        :sign-in-goes-to="$this->goes()->signIn()"
    />
@endif

<x-operator::screen-closes :goes="$this->goes()" :here="$this->itsTab()" />
