{{-- Text that is tapped. The dimming under a finger is what says so, because
     the platform gives one button style and a second filled bar beside the
     first is two ways forward rather than one. --}}
@if ($goes !== '')
    <native:pressable class="w-full py-2" @navigate="$goes" a11y-label="{{ $label }}" :press-opacity="0.6">
        <x-operator::note>{{ $label }}</x-operator::note>
    </native:pressable>
@else
    <native:pressable class="w-full py-2" @press="{{ $tap }}" a11y-label="{{ $label }}" :press-opacity="0.6">
        <x-operator::note>{{ $label }}</x-operator::note>
    </native:pressable>
@endif
