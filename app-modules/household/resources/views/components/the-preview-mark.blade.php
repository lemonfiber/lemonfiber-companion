{{-- First on the screen, so nothing below it can be read as a member's own.
     The control is the operator's way out of the member's side, because the
     member's screens lead nowhere near the operator's. --}}
<x-design::notice tone="quiet">
    <x-design::strong>{{ __('household.preview.marked') }}</x-design::strong>
    <x-design::body>{{ __('household.preview.about') }}</x-design::body>
</x-design::notice>
<x-design::action label="{{ __('household.preview.back') }}" tap="backToTheSwitchboard()" tone="tonal" />
