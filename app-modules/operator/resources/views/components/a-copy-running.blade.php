{{-- The backup or restore running on a stack, as the stack's reading says it:
     how much of how much, the part being written, and whether it is writing,
     slowed on purpose or stuck; or that nothing runs, or that the app cannot tell. --}}
@if ($copy->written === null || $copy->whole === null)
    <x-design::standing :said="__($copy->said)" :tone="$copy->tone" />
@else
    <x-design::card>
        <x-design::strong>{{ __($copy->said) }}</x-design::strong>
        <x-design::body>{{ __('stacks.copy.running.written', [
            'written' => $copy->written->figure,
            'written_unit' => __($copy->written->unit),
            'whole' => $copy->whole->figure,
            'whole_unit' => __($copy->whole->unit),
        ]) }}</x-design::body>
        <x-design::body>{{ __('stacks.copy.running.part', ['part' => $copy->part]) }}</x-design::body>
        <x-design::standing
            :said="trans_choice($copy->moves, $copy->count, $copy->limit === null ? [] : ['limit' => $copy->limit->figure, 'limit_unit' => __($copy->limit->unit)])"
            :tone="$copy->tone"
        />
    </x-design::card>
@endif
