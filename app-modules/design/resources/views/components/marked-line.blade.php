<native:row class="w-full gap-2 items-start">
<native:icon name="{{ $says->glyph() }}" ios="{{ $says->iosGlyph() }}" :size="18" color="{{ $ink }}" dark-color="{{ $paper }}" a11y-label="{{ $word }}" />
<native:text class="flex-1 font-mono text-sm font-semibold text-theme-text">{{ $line }}</native:text>
</native:row>
