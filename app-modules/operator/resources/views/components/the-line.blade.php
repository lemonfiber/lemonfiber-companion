{{-- One reading of how a stack shares its line. Where it stands and what
     that means for the house come first: they answer *why is the internet
     slow*. --}}
<x-design::standing :said="__($line->standsSaid)" :tone="$line->tone" />
<x-design::body>{{ $line->means }}</x-design::body>

{{-- Each direction in the stack's own sentence, which carries the figure
     a share is a share of. --}}
<x-design::section>
    <x-design::row :headline="__('stacks.line.down', ['says' => $line->downSays])" />
    <x-design::row
        :headline="__('stacks.line.up', ['says' => $line->upSays])"
        :supporting="$line->uploadCost === '' ? '' : __('stacks.line.upload_cost', ['costs' => $line->uploadCost])"
    />
</x-design::section>

<x-design::section :label="__('stacks.line.capacity')">
    @if ($line->measured !== null)
        {{-- Down and up apart, and always with how the figure came to be:
             a declared figure is a claim, and one measured beside the tunnel
             says nothing about the tunnel. --}}
        <x-design::row
            :headline="__('stacks.line.carries', [
                'down' => $line->measured->downFigure,
                'down_unit' => __($line->measured->downUnit),
                'up' => $line->measured->upFigure,
                'up_unit' => __($line->measured->upUnit),
            ])"
            :supporting="trans_choice($line->measured->agoSaid, $line->measured->agoCount)"
        />
        <x-design::row :headline="__($line->measured->measuredSaid)" />
        <x-design::row :headline="__($line->measured->tunnelSaid)" />
    @else
        <x-design::row :headline="__('stacks.line.unmeasured')" />
    @endif
</x-design::section>

<x-design::section :label="__('stacks.line.monthly_cap')">
    @if ($line->cap !== null)
        {{-- Nought is a cap and drawn as one; what reaching it does is on the
             same row, because *you have reached your cap* without it is not
             an answer. --}}
        <x-design::row
            :headline="__('stacks.line.capped_at', ['figure' => $line->cap->figure, 'unit' => __($line->cap->unit)])"
            :supporting="__($line->cap->doesSaid)"
        />

        @if ($line->cap->standingSaid !== '')
            <x-design::row :headline="__($line->cap->standingSaid)" />
        @endif
    @else
        <x-design::row :headline="__('stacks.line.uncapped')" />
    @endif

    @if ($line->spentCap !== '')
        <x-design::row :headline="$line->spentCap" />
    @endif
</x-design::section>

<x-design::section :label="__('stacks.line.hours')">
    @if ($line->hours !== null)
        <x-design::row
            :headline="__('stacks.line.up_from', ['from' => $line->hours->from, 'to' => $line->hours->to])"
            :supporting="$line->hours->zone === '' ? '' : __('stacks.line.zone', ['zone' => $line->hours->zone])"
        />
    @else
        <x-design::row :headline="__('stacks.line.no_hours')" />
    @endif
</x-design::section>

<x-design::section :label="__('stacks.line.untouched')">
    @forelse ($line->untouched as $one)
        <x-design::row :headline="$one" />
    @empty
        <x-design::row :headline="__('stacks.line.nothing_untouched')" />
    @endforelse
</x-design::section>

{{-- What to know before trusting any of the above, said by the stack. --}}
@forelse ($line->cautions as $caution)
    <x-design::note>{{ $caution }}</x-design::note>
@empty
    <x-design::note>{{ __('stacks.line.no_cautions') }}</x-design::note>
@endforelse
