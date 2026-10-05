<native:row class="w-full gap-3 items-center">
@if ($word !== '')
<native:icon name="{{ $says->glyph() }}" ios="{{ $says->iosGlyph() }}" :size="28" color="{{ $colour }}" a11y-label="{{ $word }}" />
@else
<native:icon name="{{ $says->glyph() }}" ios="{{ $says->iosGlyph() }}" :size="28" color="{{ $colour }}" />
@endif
<native:column class="flex-1 gap-1">
<x-design::title>{{ $said }}</x-design::title>
@if ($word !== '')
<x-design::note>{{ $word }}</x-design::note>
@endif
@if ($note !== '')
<x-design::note>{{ $note }}</x-design::note>
@endif
</native:column>
</native:row>
