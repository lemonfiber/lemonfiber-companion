{{-- A pressable, for the poster's reason: its label is said once and the
     words drawn inside it are not said again. Pressing it asks the core to
     open the channel, and the player comes on screen over Home. --}}
<native:pressable class="w-48 min-h-12" a11y-label="{{ $named }}" :press-opacity="0.6" @press="{{ $channel->tap }}">
<native:column class="w-full aspect-[16/9] justify-between gap-1 rounded border border-theme-line bg-theme-raised p-3">
    <native:text class="text-[12] font-medium text-theme-muted" font="DMMono-Medium" :fixed-size="true" :max-lines="1">{{ $above }}</native:text>
    <native:text class="text-[15] font-extrabold text-theme-text" font="GolosText-ExtraBold" :fixed-size="true" :max-lines="2">{{ $lettered }}</native:text>
    <native:text class="text-[12] font-medium text-theme-muted" font="DMMono-Medium" :fixed-size="true" :max-lines="1">{{ $below }}</native:text>
</native:column>
</native:pressable>
