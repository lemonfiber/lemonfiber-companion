{{-- A programme in the list, across it and taller than a finger needs. The
     one on now is drawn raised and tunes the channel; a later one opens its
     sheet. --}}
@if ($programme->isOnNow)
<native:pressable class="w-full min-h-16 p-1" a11y-label="{{ $named }}" :press-opacity="0.6" @press="{{ $programme->tap }}">
<native:column class="w-full justify-center gap-1 rounded border border-theme-text bg-theme-raised p-2">
    <native:text class="text-[15] font-extrabold text-theme-text" font="GolosText-ExtraBold" :fixed-size="true" :max-lines="2">{{ $programme->title }}</native:text>
    <native:text class="text-[12] font-medium text-theme-muted" font="DMMono-Medium" :fixed-size="true" :max-lines="1">{{ $programme->times }}</native:text>
</native:column>
</native:pressable>
@else
<native:pressable class="w-full min-h-16 p-1" a11y-label="{{ $named }}" :press-opacity="0.6" @press="{{ $programme->tap }}">
<native:column class="w-full justify-center gap-1 rounded border border-theme-line p-2">
    <native:text class="text-[15] font-medium text-theme-text" font="GolosText-Medium" :fixed-size="true" :max-lines="2">{{ $programme->title }}</native:text>
    <native:text class="text-[12] font-medium text-theme-muted" font="DMMono-Medium" :fixed-size="true" :max-lines="1">{{ $programme->times }}</native:text>
</native:column>
</native:pressable>
@endif
