<native:row class="w-full gap-3 items-center">
<native:icon name="{{ $says->glyph() }}" ios="{{ $says->iosGlyph() }}" :size="28" color="{{ $ink }}" dark-color="{{ $paper }}" />
<native:column class="flex-1 gap-1">
<x-design::title>{{ $said }}</x-design::title>
@if ($note !== '')
<x-design::note>{{ $note }}</x-design::note>
@endif
</native:column>
</native:row>
