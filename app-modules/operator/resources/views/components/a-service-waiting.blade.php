{{-- A service the stack holds back, and everything it waits for, never by value. --}}
<x-design::strong>{{ __('plugins.waiting.held_back', ['service' => $waiting->service]) }}</x-design::strong>
@forelse ($waiting->wants as $want)
    <x-design::note>{{ __($want->key, $want->filling) }}</x-design::note>
@empty
    <x-design::note>{{ __('plugins.waiting.nothing_said') }}</x-design::note>
@endforelse
