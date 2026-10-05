@if ($goes !== '')
<native:pressable native:key="{{ $named }}" class="w-full min-h-12 flex-row items-center justify-between gap-2 py-2" a11y-label="{{ $named }}" :press-opacity="0.6" @navigate="$goes, $carries">
    <native:text class="flex-1 text-[15] font-medium text-theme-text" font="GolosText-Medium">{{ $label }}</native:text>
    <native:icon name="chevron_right" ios="chevron.right" :size="20" color="{{ $colour }}" />
</native:pressable>
@else
<native:pressable native:key="{{ $named }}" class="w-full min-h-12 flex-row items-center justify-between gap-2 py-2" a11y-label="{{ $named }}" :press-opacity="0.6" @press="{{ $tap }}">
    <native:text class="flex-1 text-[15] font-medium text-theme-text" font="GolosText-Medium">{{ $label }}</native:text>
    <native:icon name="chevron_right" ios="chevron.right" :size="20" color="{{ $colour }}" />
</native:pressable>
@endif
