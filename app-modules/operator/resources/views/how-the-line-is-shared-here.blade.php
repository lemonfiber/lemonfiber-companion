<x-operator::screen-opens :title="$this->stack()->name()->shown()" />

@if ($this->answer()->went->cameBack())
<x-operator::content>
    {{-- Where the line stands and what that means for the house, first:
         it is the answer to *why is the internet slow*. --}}
    <x-design::standing :said="__($this->answer()->standsSaid)" :tone="$this->answer()->tone" />
    <x-design::body>{{ $this->answer()->means }}</x-design::body>

    {{-- Each direction in the stack's own sentence, which carries the figure
         a share is a share of. --}}
    <x-design::section>
        <x-design::row :headline="__('stacks.line.down', ['says' => $this->answer()->downSays])" />
        <x-design::row
            :headline="__('stacks.line.up', ['says' => $this->answer()->upSays])"
            :supporting="$this->answer()->uploadCost === '' ? '' : __('stacks.line.upload_cost', ['costs' => $this->answer()->uploadCost])"
        />
    </x-design::section>

    <x-design::section :label="__('stacks.line.capacity')">
        @if ($this->answer()->measured !== null)
            {{-- Down and up apart, and always with how the figure came to be:
                 a declared figure is a claim, and one measured beside the tunnel
                 says nothing about the tunnel. --}}
            <x-design::row
                :headline="__('stacks.line.carries', [
                    'down' => $this->answer()->measured->downFigure,
                    'down_unit' => __($this->answer()->measured->downUnit),
                    'up' => $this->answer()->measured->upFigure,
                    'up_unit' => __($this->answer()->measured->upUnit),
                ])"
                :supporting="trans_choice($this->answer()->measured->agoSaid, $this->answer()->measured->agoCount)"
            />
            <x-design::row :headline="__($this->answer()->measured->measuredSaid)" />
            <x-design::row :headline="__($this->answer()->measured->tunnelSaid)" />
        @else
            <x-design::row :headline="__('stacks.line.unmeasured')" />
        @endif
    </x-design::section>

    <x-design::section :label="__('stacks.line.monthly_cap')">
        @if ($this->answer()->cap !== null)
            {{-- Nought is a cap and drawn as one; what reaching it does is on the
                 same row, because *you have reached your cap* without it is not
                 an answer. --}}
            <x-design::row
                :headline="__('stacks.line.capped_at', ['figure' => $this->answer()->cap->figure, 'unit' => __($this->answer()->cap->unit)])"
                :supporting="__($this->answer()->cap->doesSaid)"
            />

            @if ($this->answer()->cap->standingSaid !== '')
                <x-design::row :headline="__($this->answer()->cap->standingSaid)" />
            @endif
        @else
            <x-design::row :headline="__('stacks.line.uncapped')" />
        @endif

        @if ($this->answer()->spentCap !== '')
            <x-design::row :headline="$this->answer()->spentCap" />
        @endif
    </x-design::section>

    <x-design::section :label="__('stacks.line.untouched')">
        @forelse ($this->answer()->untouched as $one)
            <x-design::row :headline="$one" />
        @empty
            <x-design::row :headline="__('stacks.line.nothing_untouched')" />
        @endforelse
    </x-design::section>

    {{-- What to know before trusting any of the above, said by the stack. --}}
    @forelse ($this->answer()->cautions as $caution)
        <x-design::note>{{ $caution }}</x-design::note>
    @empty
        <x-design::note>{{ __('stacks.line.no_cautions') }}</x-design::note>
    @endforelse

    <x-design::note>{{ __('stacks.line.changed_at_the_machine') }}</x-design::note>

    <x-design::action label="{{ __('health.ask_again') }}" tap="again()" tone="tonal" />
</x-operator::content>
@else
    <x-operator::what-stopped-the-reading
        :went="$this->answer()->went"
        :sign-in-goes-to="$this->goes()->signIn()"
    />
@endif

<x-operator::screen-closes :goes="$this->goes()" here="health" />
