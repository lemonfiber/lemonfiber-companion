{{-- The eyebrow over the figure, the figure with its whole and its unit on
     one line, and what it means under it. Each branch writes its classes out,
     so every one of them can be read. --}}
<native:column class="gap-2">
@if ($eyebrow !== '')
<native:text class="text-[12] font-medium uppercase text-theme-faint" font="GolosText-Medium">{{ $eyebrow }}</native:text>
@endif
@if ($figure === '')
<native:text class="text-[15] font-medium text-theme-muted" font="GolosText-Medium">{{ $absent }}</native:text>
@else
<native:row class="gap-2 items-end">
<native:text class="text-[27] font-medium text-theme-text" font="DMMono-Medium">{{ $figure }}</native:text>
@if ($outOf !== '')
<native:text class="text-[13] text-theme-faint" font="DMMono-Regular">{{ $outOf }}</native:text>
@endif
@if ($unit !== '')
<native:text class="text-[13] text-theme-faint" font="DMMono-Regular">{{ $unit }}</native:text>
@endif
</native:row>
@endif
@if ($caption !== '')
<native:text class="text-[13] font-medium text-theme-muted" font="GolosText-Medium">{{ $caption }}</native:text>
@endif
</native:column>
