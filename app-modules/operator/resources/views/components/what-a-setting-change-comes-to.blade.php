{{-- What a change to the settings store comes to, as the stack reported it, rehearsed or written. --}}
<x-design::notice>
    <x-design::strong>{{ __($change->didSaid) }}</x-design::strong>
</x-design::notice>
<x-operator::guards-moved :guards="$change->guards" />
@forelse ($change->moves as $move)
    <x-design::body>{{ $move->toDefault ? __('plugins.settings.moves_to_default', ['setting' => $move->setting]) : __('plugins.settings.moves_to', ['setting' => $move->setting, 'value' => $move->value]) }}</x-design::body>
    <x-design::note>{{ $move->wasSet ? __('plugins.settings.was', ['was' => $move->was]) : __('plugins.settings.was_default') }}</x-design::note>
    @if ($move->consequence !== '')
        <x-design::note>{{ __('plugins.settings.consequence', ['consequence' => $move->consequence]) }}</x-design::note>
    @endif
@empty
@endforelse
@if ($change->restarts === '')
    <x-design::body>{{ __('plugins.settings.restarts_nothing') }}</x-design::body>
@else
    <x-design::body>{{ __('plugins.settings.restarts', ['services' => $change->restarts]) }}</x-design::body>
@endif
@unless ($change->regenerates === '')
    <x-design::note>{{ __('plugins.settings.regenerates', ['services' => $change->regenerates]) }}</x-design::note>
@endunless
@unless ($change->starts === '')
    <x-design::body>{{ __('plugins.settings.starts', ['services' => $change->starts]) }}</x-design::body>
@endunless
@forelse ($change->waiting as $waiting)
    <x-operator::a-service-waiting :waiting="$waiting" />
@empty
@endforelse
@if ($change->unrestarted !== '')
    <x-design::note>{{ __('plugins.settings.unrestarted', ['why' => $change->unrestarted]) }}</x-design::note>
@endif
