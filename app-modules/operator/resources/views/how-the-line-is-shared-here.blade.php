<x-operator::screen-opens :title="$this->stack()->name()->shown()" />

@if ($this->answer()->went->cameBack())
<native:column class="w-full gap-4 px-6 py-4">
    {{-- Where the line stands and what that means for the house, first:
         it is the answer to *why is the internet slow*. --}}
    <x-operator::emphasis>{{ __($this->answer()->standsSaid) }}</x-operator::emphasis>
    <native:text>{{ $this->answer()->means }}</native:text>

    {{-- Each direction in the stack's own sentence, which carries the figure
         a share is a share of. --}}
    <x-operator::note>{{ __('stacks.line.down', ['says' => $this->answer()->downSays]) }}</x-operator::note>
    <x-operator::note>{{ __('stacks.line.up', ['says' => $this->answer()->upSays]) }}</x-operator::note>

    @if ($this->answer()->uploadCost !== '')
        <x-operator::note>{{ __('stacks.line.upload_cost', ['costs' => $this->answer()->uploadCost]) }}</x-operator::note>
    @endif

    <x-operator::emphasis>{{ __('stacks.line.capacity') }}</x-operator::emphasis>
    @if ($this->answer()->measured !== null)
        {{-- Down and up apart, and always with how the figure came to be:
             a declared figure is a claim, and one measured beside the tunnel
             says nothing about the tunnel. --}}
        <native:text>{{ __('stacks.line.carries', [
            'down' => $this->answer()->measured->downFigure,
            'down_unit' => __($this->answer()->measured->downUnit),
            'up' => $this->answer()->measured->upFigure,
            'up_unit' => __($this->answer()->measured->upUnit),
        ]) }}</native:text>
        <x-operator::note>{{ __($this->answer()->measured->measuredSaid) }}</x-operator::note>
        <x-operator::note>{{ __($this->answer()->measured->tunnelSaid) }}</x-operator::note>
        <x-operator::note>{{ trans_choice($this->answer()->measured->agoSaid, $this->answer()->measured->agoCount) }}</x-operator::note>
    @else
        <x-operator::note>{{ __('stacks.line.unmeasured') }}</x-operator::note>
    @endif

    <x-operator::emphasis>{{ __('stacks.line.monthly_cap') }}</x-operator::emphasis>
    @if ($this->answer()->cap !== null)
        {{-- Nought is a cap and drawn as one; what reaching it does is on the
             same line, because *you have reached your cap* without it is not
             an answer. --}}
        <native:text>{{ __('stacks.line.capped_at', ['figure' => $this->answer()->cap->figure, 'unit' => __($this->answer()->cap->unit)]) }}</native:text>
        <x-operator::note>{{ __($this->answer()->cap->doesSaid) }}</x-operator::note>

        @if ($this->answer()->cap->standingSaid !== '')
            <x-operator::note>{{ __($this->answer()->cap->standingSaid) }}</x-operator::note>
        @endif
    @else
        <x-operator::note>{{ __('stacks.line.uncapped') }}</x-operator::note>
    @endif

    @if ($this->answer()->spentCap !== '')
        <x-operator::note>{{ $this->answer()->spentCap }}</x-operator::note>
    @endif

    <x-operator::emphasis>{{ __('stacks.line.untouched') }}</x-operator::emphasis>
    @forelse ($this->answer()->untouched as $one)
        <x-operator::note>{{ $one }}</x-operator::note>
    @empty
        <x-operator::note>{{ __('stacks.line.nothing_untouched') }}</x-operator::note>
    @endforelse

    {{-- What to know before trusting any of the above, said by the stack. --}}
    @forelse ($this->answer()->cautions as $caution)
        <x-operator::note>{{ $caution }}</x-operator::note>
    @empty
        <x-operator::note>{{ __('stacks.line.no_cautions') }}</x-operator::note>
    @endforelse

    <x-operator::note>{{ __('stacks.line.changed_at_the_machine') }}</x-operator::note>

    <x-operator::action label="{{ __('health.ask_again') }}" tap="again()" />
</native:column>
@else
    <x-operator::what-stopped-the-reading
        :went="$this->answer()->went"
        :sign-in-goes-to="$this->goes()->signIn()"
    />
@endif

<x-operator::screen-closes :goes="$this->goes()" here="health" />
