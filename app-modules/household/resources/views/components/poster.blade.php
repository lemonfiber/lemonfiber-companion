@use('Modules\Household\Internal\ViewModels\HowAPosterIsLettered')
{{-- A pressable whether or not it opens anything, because it is the
     platform's one element that reads as one: its label is said, and the
     words drawn inside it are not said again. A title opens its own screen,
     which reads it in full; something part-way through plays on from where
     it was left; a request opens nothing. Each step's lettering is written
     out, so every class on it can be read. --}}
@if ($poster->tap !== '')
<native:pressable class="w-32 min-h-12" a11y-label="{{ $named }}" :press-opacity="0.6" @press="{{ $poster->tap }}">
@elseif ($poster->goes !== '')
<native:pressable class="w-32 min-h-12" a11y-label="{{ $named }}" :press-opacity="0.6" @navigate="$poster->goes">
@else
<native:pressable class="w-32 min-h-12" a11y-label="{{ $named }}">
@endif
<native:column class="w-full aspect-[2/3] justify-between gap-2 rounded border border-theme-line bg-theme-raised p-3">
    <native:text class="text-[12] font-medium text-theme-muted" font="DMMono-Medium" :fixed-size="true" :max-lines="1">{{ $above }}</native:text>
    @if ($poster->lettered === HowAPosterIsLettered::Large)
    <native:text class="text-[27] font-extrabold text-theme-text" font="GolosText-ExtraBold" :fixed-size="true" :max-lines="$lines">{{ $poster->titled }}</native:text>
    @elseif ($poster->lettered === HowAPosterIsLettered::Middle)
    <native:text class="text-[15] font-extrabold text-theme-text" font="GolosText-ExtraBold" :fixed-size="true" :max-lines="$lines">{{ $poster->titled }}</native:text>
    @else
    <native:text class="text-[13] font-extrabold text-theme-text" font="GolosText-ExtraBold" :fixed-size="true" :max-lines="$lines">{{ $poster->titled }}</native:text>
    @endif
</native:column>
</native:pressable>
