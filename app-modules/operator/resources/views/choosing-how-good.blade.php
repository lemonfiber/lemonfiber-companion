<x-operator::screen-opens :title="$this->stack()->name()->shown()" />

@if ($this->answer()->went->cameBack())
<x-operator::content>
    {{-- What became of the choice, first: a rehearsal and a held choice each
         say so in words of their own, so neither reads as recorded. --}}
    <x-design::heading>{{ __($this->answer()->becameSaid) }}</x-design::heading>

    @if ($this->mayConfirm())
        {{-- Why it was held is what the stack said playing each held preset
             costs here. The yes is a tap of its own, under the reason. --}}
        <x-design::notice>
            @forelse ($this->answer()->heldBecause as $because)
                <x-design::body>{{ $because }}</x-design::body>
            @empty
                <x-design::body>{{ __('quality.held_unexplained') }}</x-design::body>
            @endforelse
        </x-design::notice>
        <x-design::action label="{{ __('quality.confirm') }}" tap="confirm()" />
    @endif

    {{-- A hand-edit is respected, and putting the preset back over it is not
         offered: its whole effect is overwriting what the operator changed. --}}
    @if ($this->answer()->customised)
        <x-design::notice>
            <x-design::body>{{ __('quality.customised') }}</x-design::body>
            <x-design::note>{{ __('quality.not_put_back') }}</x-design::note>
        </x-design::notice>
    @endif

    {{-- The presets in force, the overall choice first, each in the stack's
         words with what an hour of it costs. --}}
    @forelse ($this->answer()->presets as $inForce)
        <x-design::card>
            <x-design::strong>{{ $inForce->preset }}</x-design::strong>
            <x-design::note>{{ __('quality.for', ['scope' => $inForce->scope]) }}</x-design::note>
            <x-design::body>{{ $inForce->means }}</x-design::body>
            <x-design::body>{{ $inForce->resolution }}</x-design::body>
            <x-design::body>{{ __('quality.per_hour', ['size' => $inForce->sizePerHour]) }}</x-design::body>
            <x-design::note>{{ $inForce->transcoding }}</x-design::note>
            {{-- A property of this machine, not of the preset. --}}
            @if ($inForce->transcodesHere)
                <x-design::strong>{{ __('quality.transcodes_here') }}</x-design::strong>
            @endif
        </x-design::card>
    @empty
        <x-design::body>{{ __('quality.no_presets') }}</x-design::body>
    @endforelse

    {{-- Music has no resolution, so it is drawn by its format and never in a
         resolution's place. --}}
    <x-design::heading>{{ __('quality.music.heading') }}</x-design::heading>
    @if ($this->answer()->music->format !== '')
        <x-design::card>
            <x-design::strong>{{ $this->answer()->music->format }}</x-design::strong>
            <x-design::note>{{ __('quality.for', ['scope' => $this->answer()->music->scope]) }}</x-design::note>
            <x-design::body>{{ $this->answer()->music->means }}</x-design::body>
            <x-design::body>{{ __('quality.music.targets', ['targets' => $this->answer()->music->targets]) }}</x-design::body>
            <x-design::body>{{ __('quality.per_hour', ['size' => $this->answer()->music->sizePerHour]) }}</x-design::body>
            <x-design::note>{{ $this->answer()->music->note }}</x-design::note>
        </x-design::card>
    @else
        <x-design::body>{{ __('quality.music.unset') }}</x-design::body>
    @endif

    @if ($this->music !== null)
        {{-- What choosing a format for music did, and what its service made
             of it. --}}
        <x-design::card>
            <x-design::strong>{{ __($this->music->becameSaid) }}</x-design::strong>
            <x-design::body>{{ $this->music->format->format }}</x-design::body>
            <x-design::note>{{ __($this->music->appliedSaid) }}</x-design::note>
            @if ($this->music->detail !== '')
                <x-design::note>{{ $this->music->detail }}</x-design::note>
            @endif
        </x-design::card>
    @endif

    {{-- Choosing, in the stack's words: it takes the name or refuses it. The
         two fields and the button that sends them are one card. --}}
    <x-design::heading>{{ __('quality.choose.heading') }}</x-design::heading>
    <x-design::card>
        <native:outlined-text-input
            native:model="preset"
            label="{{ __('quality.choose.preset') }}"
            supporting="{{ __('quality.choose.preset_help') }}"
        />
        <native:outlined-text-input
            native:model="kind"
            label="{{ __('quality.choose.kind') }}"
            supporting="{{ __('quality.choose.kind_help') }}"
        />
        <x-design::action label="{{ __('quality.choose.act') }}" tap="choose()" />
    </x-design::card>

    {{-- Upgrading what is already here is its own act, described kind by
         kind before anything is fetched. --}}
    <x-design::heading>{{ __('quality.upgrade.heading') }}</x-design::heading>
    <x-design::note>{{ __('quality.upgrade.apart') }}</x-design::note>

    @if ($this->upgrading !== null)
        {{-- What stood in the way is drawn here, under the upgrade, so what
             is in force stays on the screen above it. --}}
        @if ($this->upgrading->went->cameBack())
            <x-design::body>{{ __($this->upgrading->headingSaid) }}</x-design::body>
            {{-- Kind by kind, each at its own preset and its own cost an hour. --}}
            @forelse ($this->upgrading->kinds as $covered)
                <x-design::card>
                    <x-design::strong>{{ __('quality.upgrade.kind', ['kind' => $covered->kind, 'preset' => $covered->preset]) }}</x-design::strong>
                    <x-design::body>{{ __('quality.per_hour', ['size' => $covered->sizePerHour]) }}</x-design::body>
                    <x-design::note>{{ __($covered->askingSaid) }}</x-design::note>
                    @if ($covered->detail !== '')
                        <x-design::note>{{ $covered->detail }}</x-design::note>
                    @endif
                </x-design::card>
            @empty
                <x-design::body>{{ __('quality.upgrade.nothing') }}</x-design::body>
            @endforelse
        @elseif ($this->upgrading->went->isSignedIn)
            <x-design::notice tone="unknown">
                <x-design::strong>{{ __($this->upgrading->went->met) }}</x-design::strong>
                <x-design::body>{{ __($this->upgrading->went->remedy) }}</x-design::body>
            </x-design::notice>
        @else
            <x-design::notice tone="unknown">
                <x-design::body>{{ __('connection.session_has_ended') }}</x-design::body>
            </x-design::notice>
        @endif
    @endif

    @if ($this->mayUpgrade())
        <x-design::action label="{{ __('quality.upgrade.agree') }}" tap="upgrade()" />
    @else
        <x-design::action label="{{ __('quality.upgrade.describe') }}" tap="describe()" tone="tonal" />
    @endif

    <x-design::action label="{{ __('health.ask_again') }}" tap="again()" tone="tonal" />
</x-operator::content>
@else
    <x-operator::what-stopped-the-reading
        :went="$this->answer()->went"
        :sign-in-goes-to="$this->goes()->signIn()"
    />
@endif

<x-operator::screen-closes :goes="$this->goes()" :here="$this->itsTab()" />
