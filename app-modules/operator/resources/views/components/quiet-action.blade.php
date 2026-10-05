{{-- Text that is tapped, in the colour of the operator's own actions, which
     paints as muted text where a member's theme is on the glass. The dimming
     under a finger says it is tapped, because the platform gives one button
     style and a second filled bar beside the first is two ways forward rather
     than one.

     `G3-R16`: the target is `min-h-12` — 48, which is Android's minimum and
     above iOS's 44 — and the words inside it are left alone. A line of note
     text with `py-2` around it is about thirty, and this is the control
     somebody reaches for one-handed when something has gone wrong. The text is
     centred in it rather than sitting at the top, because a target whose ink
     is at one end of it is a target people aim at the wrong half of. --}}
@if ($goes !== '')
    <native:pressable native:key="{{ $label }}" class="w-full min-h-12 justify-center py-2" @navigate="$goes" a11y-label="{{ $label }}" :press-opacity="0.6">
        <native:text class="text-[13] font-medium text-theme-own-action" font="GolosText-Medium">{{ $label }}</native:text>
    </native:pressable>
@else
    <native:pressable native:key="{{ $label }}" class="w-full min-h-12 justify-center py-2" @press="{{ $tap }}" a11y-label="{{ $label }}" :press-opacity="0.6">
        <native:text class="text-[13] font-medium text-theme-own-action" font="GolosText-Medium">{{ $label }}</native:text>
    </native:pressable>
@endif
