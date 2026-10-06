{{-- A row that goes somewhere or does something is one pressable target with
     a chevron at its end; one that does neither is a plain row. What each
     holds is the same, written out in each branch. A row with no tone has no
     standing to say, and leads with a blank the width of a port so its name
     lines up with the rows that have one. --}}
@if ($goes !== '')
<native:pressable native:key="{{ $named }}" class="w-full min-h-12 flex-row items-center gap-3 px-4 py-2" a11y-label="{{ $named }}" :press-opacity="0.6" @navigate="$goes">
    @if ($tone !== '')
    <x-operator::port :tone="$tone" />
    @else
    <native:column class="w-10 h-10" />
    @endif
    <native:column class="flex-1 gap-1">
        <native:text class="text-[15] font-extrabold text-theme-text" font="GolosText-ExtraBold">{{ $name }}</native:text>
        @if ($said !== '')
        <native:text class="text-[13] font-medium text-theme-muted" font="GolosText-Medium">{{ $said }}</native:text>
        @endif
    </native:column>
    @if ($figure !== '')
    <x-operator::stamp>{{ $figure }}</x-operator::stamp>
    @endif
    <native:icon name="chevron_right" ios="chevron.right" :size="20" color="{{ $chevron }}" />
</native:pressable>
@elseif ($tap !== '')
<native:pressable native:key="{{ $named }}" class="w-full min-h-12 flex-row items-center gap-3 px-4 py-2" a11y-label="{{ $named }}" :press-opacity="0.6" @press="{{ $tap }}">
    @if ($tone !== '')
    <x-operator::port :tone="$tone" />
    @else
    <native:column class="w-10 h-10" />
    @endif
    <native:column class="flex-1 gap-1">
        <native:text class="text-[15] font-extrabold text-theme-text" font="GolosText-ExtraBold">{{ $name }}</native:text>
        @if ($said !== '')
        <native:text class="text-[13] font-medium text-theme-muted" font="GolosText-Medium">{{ $said }}</native:text>
        @endif
    </native:column>
    @if ($figure !== '')
    <x-operator::stamp>{{ $figure }}</x-operator::stamp>
    @endif
    <native:icon name="chevron_right" ios="chevron.right" :size="20" color="{{ $chevron }}" />
</native:pressable>
@else
<native:row class="w-full min-h-12 items-center gap-3 px-4 py-2">
    @if ($tone !== '')
    <x-operator::port :tone="$tone" />
    @else
    <native:column class="w-10 h-10" />
    @endif
    <native:column class="flex-1 gap-1">
        <native:text class="text-[15] font-extrabold text-theme-text" font="GolosText-ExtraBold">{{ $name }}</native:text>
        @if ($said !== '')
        <native:text class="text-[13] font-medium text-theme-muted" font="GolosText-Medium">{{ $said }}</native:text>
        @endif
    </native:column>
    @if ($figure !== '')
    <x-operator::stamp>{{ $figure }}</x-operator::stamp>
    @endif
</native:row>
@endif
<x-operator::rule />
