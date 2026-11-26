@if ($written === '')
    <x-design::standing :said="$title" :tone="$copy->tone" />
@else
    <x-design::card>
        <x-design::strong>{{ $title }}</x-design::strong>
        <x-design::progress :done="$copy->done" :said="$written" />
        <x-design::body>{{ $written }}</x-design::body>
        <x-design::body>{{ __('stacks.copy.running.part', ['part' => $copy->part]) }}</x-design::body>
        <x-design::note>{{ $started }}</x-design::note>
        <x-design::standing :said="$moves" :tone="$copy->tone" />
    </x-design::card>
@endif
