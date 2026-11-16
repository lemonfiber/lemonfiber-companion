{{-- A pressable, for the poster's reason. The programme on now is drawn
     raised and tunes the channel; a later one opens its sheet. --}}
@if ($cell->isOnNow)
<native:pressable class="h-16 min-h-12 p-1" :width="$cell->width" a11y-label="{{ $named }}" :press-opacity="0.6" @press="{{ $cell->tap }}">
<native:column class="w-full h-full justify-center gap-1 rounded border border-theme-text bg-theme-raised px-2">
    <native:text class="text-[15] font-extrabold text-theme-text" font="GolosText-ExtraBold" :fixed-size="true" :max-lines="1">{{ $cell->title }}</native:text>
    <native:text class="text-[12] font-medium text-theme-muted" font="DMMono-Medium" :fixed-size="true" :max-lines="1">{{ $cell->times }}</native:text>
</native:column>
</native:pressable>
@else
<native:pressable class="h-16 min-h-12 p-1" :width="$cell->width" a11y-label="{{ $named }}" :press-opacity="0.6" @press="{{ $cell->tap }}">
<native:column class="w-full h-full justify-center gap-1 rounded border border-theme-line px-2">
    <native:text class="text-[15] font-medium text-theme-text" font="GolosText-Medium" :fixed-size="true" :max-lines="1">{{ $cell->title }}</native:text>
    <native:text class="text-[12] font-medium text-theme-muted" font="DMMono-Medium" :fixed-size="true" :max-lines="1">{{ $cell->times }}</native:text>
</native:column>
</native:pressable>
@endif
