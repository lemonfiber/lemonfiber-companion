<x-operator::screen-opens :title="$this->stack()->name()->shown()" />

@if ($this->answer()->went->cameBack())
<x-operator::content>
    {{-- What became of the choice, first: a rehearsal and a held choice each
         say so in words of their own, so neither reads as recorded. --}}
    <x-operator::emphasis>{{ __($this->answer()->becameSaid) }}</x-operator::emphasis>

    @if ($this->mayConfirm())
        {{-- Why it was held is what the stack said playing each held preset
             costs here. The yes is a tap of its own, under the reason. --}}
        @forelse ($this->answer()->heldBecause as $because)
            <native:text>{{ $because }}</native:text>
        @empty
            <x-operator::note>{{ __('quality.held_unexplained') }}</x-operator::note>
        @endforelse
        <x-operator::action label="{{ __('quality.confirm') }}" tap="confirm()" />
    @endif

    {{-- A hand-edit is respected, and putting the preset back over it is not
         offered: its whole effect is overwriting what the operator changed. --}}
    @if ($this->answer()->customised)
        <x-operator::note>{{ __('quality.customised') }}</x-operator::note>
        <x-operator::note>{{ __('quality.not_put_back') }}</x-operator::note>
    @endif

    {{-- The presets in force, the overall choice first, each in the stack's
         words with what an hour of it costs. --}}
    @forelse ($this->answer()->presets as $inForce)
        <x-operator::entry>
            <x-operator::emphasis>{{ $inForce->preset }}</x-operator::emphasis>
            <x-operator::note>{{ __('quality.for', ['scope' => $inForce->scope]) }}</x-operator::note>
            <native:text>{{ $inForce->means }}</native:text>
            <native:text>{{ $inForce->resolution }}</native:text>
            <native:text>{{ __('quality.per_hour', ['size' => $inForce->sizePerHour]) }}</native:text>
            <x-operator::note>{{ $inForce->transcoding }}</x-operator::note>
            {{-- A property of this machine, not of the preset. --}}
            @if ($inForce->transcodesHere)
                <x-operator::emphasis>{{ __('quality.transcodes_here') }}</x-operator::emphasis>
            @endif
        </x-operator::entry>
    @empty
        <x-operator::note>{{ __('quality.no_presets') }}</x-operator::note>
    @endforelse

    {{-- Music has no resolution, so it is drawn by its format and never in a
         resolution's place. --}}
    <x-operator::emphasis>{{ __('quality.music.heading') }}</x-operator::emphasis>
    @if ($this->answer()->music->format !== '')
        <x-operator::entry>
            <x-operator::emphasis>{{ $this->answer()->music->format }}</x-operator::emphasis>
            <x-operator::note>{{ __('quality.for', ['scope' => $this->answer()->music->scope]) }}</x-operator::note>
            <native:text>{{ $this->answer()->music->means }}</native:text>
            <native:text>{{ __('quality.music.targets', ['targets' => $this->answer()->music->targets]) }}</native:text>
            <native:text>{{ __('quality.per_hour', ['size' => $this->answer()->music->sizePerHour]) }}</native:text>
            <x-operator::note>{{ $this->answer()->music->note }}</x-operator::note>
        </x-operator::entry>
    @else
        <x-operator::note>{{ __('quality.music.unset') }}</x-operator::note>
    @endif

    @if ($this->music !== null)
        {{-- What choosing a format for music did, and what its service made
             of it. --}}
        <x-operator::entry>
            <x-operator::emphasis>{{ __($this->music->becameSaid) }}</x-operator::emphasis>
            <native:text>{{ $this->music->format->format }}</native:text>
            <x-operator::note>{{ __($this->music->appliedSaid) }}</x-operator::note>
            @if ($this->music->detail !== '')
                <x-operator::note>{{ $this->music->detail }}</x-operator::note>
            @endif
        </x-operator::entry>
    @endif

    {{-- Choosing, in the stack's words: it takes the name or refuses it. --}}
    <x-operator::heading>{{ __('quality.choose.heading') }}</x-operator::heading>
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
    <x-operator::action label="{{ __('quality.choose.act') }}" tap="choose()" />

    {{-- Upgrading what is already here is its own act, described kind by
         kind before anything is fetched. --}}
    <x-operator::heading>{{ __('quality.upgrade.heading') }}</x-operator::heading>
    <x-operator::note>{{ __('quality.upgrade.apart') }}</x-operator::note>

    @if ($this->upgrading !== null)
        {{-- What stood in the way is drawn here, under the upgrade, so what
             is in force stays on the screen above it. --}}
        @if ($this->upgrading->went->cameBack())
            <x-operator::emphasis>{{ __($this->upgrading->headingSaid) }}</x-operator::emphasis>
            {{-- Kind by kind, each at its own preset and its own cost an hour. --}}
            @forelse ($this->upgrading->kinds as $covered)
                <x-operator::entry>
                    <x-operator::emphasis>{{ __('quality.upgrade.kind', ['kind' => $covered->kind, 'preset' => $covered->preset]) }}</x-operator::emphasis>
                    <native:text>{{ __('quality.per_hour', ['size' => $covered->sizePerHour]) }}</native:text>
                    <x-operator::note>{{ __($covered->askingSaid) }}</x-operator::note>
                    @if ($covered->detail !== '')
                        <x-operator::note>{{ $covered->detail }}</x-operator::note>
                    @endif
                </x-operator::entry>
            @empty
                <x-operator::note>{{ __('quality.upgrade.nothing') }}</x-operator::note>
            @endforelse
        @elseif ($this->upgrading->went->isSignedIn)
            <x-operator::emphasis>{{ __($this->upgrading->went->met) }}</x-operator::emphasis>
            <native:text>{{ __($this->upgrading->went->remedy) }}</native:text>
        @else
            <native:text>{{ __('connection.session_has_ended') }}</native:text>
        @endif
    @endif

    @if ($this->mayUpgrade())
        <x-operator::action label="{{ __('quality.upgrade.agree') }}" tap="upgrade()" />
    @else
        <x-operator::quiet-action label="{{ __('quality.upgrade.describe') }}" tap="describe()" />
    @endif

    <x-operator::quiet-action label="{{ __('health.ask_again') }}" tap="again()" />
</x-operator::content>
@else
    <x-operator::what-stopped-the-reading
        :went="$this->answer()->went"
        :sign-in-goes-to="$this->goes()->signIn()"
    />
@endif

<x-operator::screen-closes :goes="$this->goes()" here="health" />
