<x-operator::stack-opens :title="$this->stack()->name()->shown()" :stacks="$this->stacksToChooseFrom()" :choosing="$this->choosingAStack" />

@if ($this->answer()->went->cameBack())
<x-operator::content>
    <x-design::title>{{ __('stacks.clients.heading') }}</x-design::title>

    {{-- True of every device, so said once and before any of them: somebody
         set up on the sofa and sent home has not finished. --}}
    <x-design::body>{{ $this->answer()->onlyAtHome }}</x-design::body>

    {{-- Why playback may struggle whatever app is chosen, where anything
         strains it. --}}
    @if ($this->answer()->strainingPreset !== '')
        <x-design::notice>
            <x-design::strong>{{ __('stacks.clients.straining', ['preset' => $this->answer()->strainingPreset]) }}</x-design::strong>
            <x-design::body>{{ $this->answer()->strainingCaution }}</x-design::body>
            <x-design::note>{{ $this->answer()->strainingInstead }}</x-design::note>
        </x-design::notice>
    @endif

    @forelse ($this->answer()->devices as $device)
        <x-design::card>
            <x-design::strong>{{ $device->device }}</x-design::strong>
            <x-design::body>{{ $device->client }}</x-design::body>
            {{-- Fallback is an answer, drawn in words of its own like the rest. --}}
            <x-design::note>{{ __($device->supportSaid) }}</x-design::note>
            @if (! $device->openSource)
                <x-design::note>{{ __('stacks.clients.not_open_source') }}</x-design::note>
            @endif
            @if ($device->caution !== '')
                <x-design::note>{{ $device->caution }}</x-design::note>
            @endif
            @if ($device->instead !== '')
                <x-design::body>{{ __('stacks.clients.instead', ['instead' => $device->instead]) }}</x-design::body>
            @endif
        </x-design::card>
    @empty
        <x-design::body>{{ __('stacks.clients.no_devices') }}</x-design::body>
    @endforelse

    <x-design::note>{{ $this->answer()->nothingIsInstalled }}</x-design::note>

    <x-design::heading>{{ __('stacks.clients.trouble') }}</x-design::heading>
    @forelse ($this->answer()->troubles as $trouble)
        <x-design::card>
            <x-design::strong>{{ $trouble->symptom }}</x-design::strong>
            @forelse ($trouble->causes as $cause)
                <x-design::body>{{ $cause->because }}</x-design::body>
                <x-design::note>{{ $cause->tell }}</x-design::note>
                <x-design::note>{{ $cause->fix }}</x-design::note>
            @empty
                <x-design::note>{{ __('stacks.clients.no_causes') }}</x-design::note>
            @endforelse
        </x-design::card>
    @empty
        <x-design::body>{{ __('stacks.clients.no_trouble') }}</x-design::body>
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
