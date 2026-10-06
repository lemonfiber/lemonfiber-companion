@use('Modules\Household\Internal\ViewModels\HowAPosterIsLettered')
{{-- A pressable with nothing to press, because it is the platform's one
     element that reads as one: its label is said, and the words drawn inside
     it are not said again. Each step's lettering is written out, so every
     class on it can be read. --}}
<native:pressable class="w-32 min-h-12" a11y-label="{{ $named }}">
<native:column class="w-full aspect-[2/3] justify-between gap-2 rounded border border-theme-line bg-theme-raised p-3">
    <native:text class="text-[12] font-medium text-theme-muted" font="DMMono-Medium" :max-lines="1">{{ $above }}</native:text>
    @if ($holding->lettered === HowAPosterIsLettered::Large)
    <native:text class="text-[27] font-extrabold text-theme-text" font="GolosText-ExtraBold" :max-lines="$lines">{{ $holding->titled }}</native:text>
    @elseif ($holding->lettered === HowAPosterIsLettered::Middle)
    <native:text class="text-[15] font-extrabold text-theme-text" font="GolosText-ExtraBold" :max-lines="$lines">{{ $holding->titled }}</native:text>
    @else
    <native:text class="text-[13] font-extrabold text-theme-text" font="GolosText-ExtraBold" :max-lines="$lines">{{ $holding->titled }}</native:text>
    @endif
</native:column>
</native:pressable>
