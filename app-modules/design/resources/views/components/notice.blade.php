{{-- Raised on its tone's ground: a tint for something that wants looking at
     or is broken, and the raised surface for anything else. Each opener is
     written out, so every class on it can be read. --}}
@if ($warns)
<native:row class="w-full gap-3 rounded-2xl border-theme-line bg-theme-warn-tint p-4">
@elseif ($alarms)
<native:row class="w-full gap-3 rounded-2xl border-theme-line bg-theme-alarm-tint p-4">
@else
<native:row class="w-full gap-3 rounded-2xl border-theme-line bg-theme-raised p-4">
@endif
<native:icon name="{{ $says->glyph() }}" ios="{{ $says->iosGlyph() }}" :size="24" color="{{ $colour }}" />
<native:column class="flex-1 gap-1">
