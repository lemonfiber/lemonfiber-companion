@use('Modules\Household\Internal\ViewModels\HowAPosterIsLettered')
{{-- The tile reads as one element, for the poster's reason, and opens
     nothing itself: More is what opens the title, and two controls leading to
     one place would be heard twice. Each step's lettering is written out, so
     every class on it can be read. --}}
<native:column class="w-full gap-3">
<native:pressable class="w-full min-h-12" a11y-label="{{ $named }}">
<native:column class="w-full aspect-video justify-between gap-2 rounded border border-theme-line bg-theme-raised p-4">
    <native:text class="text-[12] font-medium text-theme-muted" font="DMMono-Medium" :max-lines="1">{{ $above }}</native:text>
    @if ($poster->lettered === HowAPosterIsLettered::Large)
    <native:text class="text-[38] font-extrabold text-theme-text" font="GolosText-ExtraBold" :max-lines="$lines">{{ $poster->titled }}</native:text>
    @elseif ($poster->lettered === HowAPosterIsLettered::Middle)
    <native:text class="text-[27] font-extrabold text-theme-text" font="GolosText-ExtraBold" :max-lines="$lines">{{ $poster->titled }}</native:text>
    @else
    <native:text class="text-[15] font-extrabold text-theme-text" font="GolosText-ExtraBold" :max-lines="$lines">{{ $poster->titled }}</native:text>
    @endif
</native:column>
</native:pressable>
{{-- Play plays it where the screen says what pressing it does. Where it
     cannot, as on a preview, it is drawn and not usable, with the reason
     beside it: a Play that is not there would hide the one action this tile
     is for. --}}
@if ($tap !== '')
<x-design::action label="{{ __('household.title.play') }}" :answersTo="$playNamed" :tap="$tap" />
@else
<x-design::action label="{{ __('household.title.play') }}" :answersTo="$playNamed" :disabled="true" />
<x-design::note>{{ __('household.preview.cannot_play') }}</x-design::note>
@endif
@if ($poster->goes !== '')
<x-design::action label="{{ __('household.hero.more') }}" :answersTo="$moreNamed" tone="tonal" :goes="$poster->goes" />
@else
<x-design::action label="{{ __('household.hero.more') }}" :answersTo="$moreNamed" tone="tonal" :disabled="true" />
@endif
</native:column>
