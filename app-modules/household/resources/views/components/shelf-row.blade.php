{{-- Sideways inside the screen's own scroll: the two move along different
     axes, so neither takes the other's gesture. --}}
<native:column class="w-full gap-2">
    <x-design::heading>{{ __($row->heading) }}</x-design::heading>
    <native:scroll-view class="w-full" horizontal :shows-indicators="false">
    <native:row class="gap-3">
        @forelse ($row->posters as $poster)
        <x-household::poster :poster="$poster" />
        @empty
        {{-- Nothing: a row with nothing in it is not drawn at all, heading
             included, so there is no empty strip here to say anything in. --}}
        @endforelse
    </native:row>
    </native:scroll-view>
</native:column>
