<native:row class="w-full gap-2 items-start">
<native:icon name="{{ $says->glyph() }}" ios="{{ $says->iosGlyph() }}" :size="18" color="{{ $colour }}" a11y-label="{{ $word }}" />
<native:text class="flex-1 text-sm font-medium text-theme-text" font="DMMono-Medium">{{ $line }}</native:text>
</native:row>
