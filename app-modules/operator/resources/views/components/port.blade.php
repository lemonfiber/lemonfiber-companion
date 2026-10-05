{{-- Each opener is written out, so every class on it can be read: the
     warning tint inside a warning edge, the alarm fill, or the raised tile on
     a hairline. --}}
@if ($warns)
<native:column class="w-10 h-10 items-center justify-center rounded border border-theme-warn bg-theme-warn-tint">
@elseif ($alarms)
<native:column class="w-10 h-10 items-center justify-center rounded border border-theme-alarm bg-theme-alarm">
@else
<native:column class="w-10 h-10 items-center justify-center rounded border border-theme-line bg-theme-raised">
@endif
@if ($label !== '')
<native:icon name="{{ $says->glyph() }}" ios="{{ $says->iosGlyph() }}" :size="20" color="{{ $colour }}" a11y-label="{{ $label }}" />
@else
<native:icon name="{{ $says->glyph() }}" ios="{{ $says->iosGlyph() }}" :size="20" color="{{ $colour }}" />
@endif
</native:column>
