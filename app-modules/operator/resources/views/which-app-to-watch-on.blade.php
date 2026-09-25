<x-operator::screen-opens :title="$this->stack()->name()->shown()" />

@if ($this->answer()->went->cameBack())
<x-operator::content>
    <x-operator::emphasis>{{ __('stacks.clients.heading') }}</x-operator::emphasis>

    {{-- True of every device, so said once and before any of them: somebody
         set up on the sofa and sent home has not finished. --}}
    <native:text>{{ $this->answer()->onlyAtHome }}</native:text>

    {{-- Why playback may struggle whatever app is chosen, where anything
         strains it. --}}
    @if ($this->answer()->strainingPreset !== '')
        <x-operator::note>{{ __('stacks.clients.straining', ['preset' => $this->answer()->strainingPreset]) }}</x-operator::note>
        <native:text>{{ $this->answer()->strainingCaution }}</native:text>
        <x-operator::note>{{ $this->answer()->strainingInstead }}</x-operator::note>
    @endif

    @forelse ($this->answer()->devices as $device)
        <x-operator::entry>
            <x-operator::emphasis>{{ $device->device }}</x-operator::emphasis>
            <native:text>{{ $device->client }}</native:text>
            {{-- Fallback is an answer, drawn in words of its own like the rest. --}}
            <x-operator::note>{{ __($device->supportSaid) }}</x-operator::note>
            @if ($device->caution !== '')
                <x-operator::note>{{ $device->caution }}</x-operator::note>
            @endif
            @if ($device->instead !== '')
                <native:text>{{ __('stacks.clients.instead', ['instead' => $device->instead]) }}</native:text>
            @endif
        </x-operator::entry>
    @empty
        <x-operator::note>{{ __('stacks.clients.no_devices') }}</x-operator::note>
    @endforelse

    <x-operator::note>{{ $this->answer()->nothingIsInstalled }}</x-operator::note>

    <x-operator::emphasis>{{ __('stacks.clients.trouble') }}</x-operator::emphasis>
    @forelse ($this->answer()->troubles as $trouble)
        <x-operator::entry>
            <x-operator::emphasis>{{ $trouble->symptom }}</x-operator::emphasis>
            @forelse ($trouble->causes as $cause)
                <native:text>{{ $cause->because }}</native:text>
                <x-operator::note>{{ $cause->tell }}</x-operator::note>
                <x-operator::note>{{ $cause->fix }}</x-operator::note>
            @empty
                <x-operator::note>{{ __('stacks.clients.no_causes') }}</x-operator::note>
            @endforelse
        </x-operator::entry>
    @empty
        <x-operator::note>{{ __('stacks.clients.no_trouble') }}</x-operator::note>
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
