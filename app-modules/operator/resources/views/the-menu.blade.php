{{-- The side menu: the stack it is about, then its five groups, every item
     drawn on every stack. --}}
<native:column class="w-full gap-4 p-4">
    <x-design::title>{{ $stack->name()->shown() }}</x-design::title>

    @forelse ($groups as $group)
        <x-design::section :label="__($group->said())">
            @forelse ($group->holds() as $item)
                <x-design::row
                    :headline="__($item->said())"
                    :answers-to="__($item->said())"
                    :goes="$item->screen()->forTheStack($stack->id())"
                    :icon="$item->glyph()"
                    :ios-icon="$item->iosGlyph()"
                />
            @empty
                {{-- Nothing: every group holds an item. --}}
            @endforelse
        </x-design::section>
    @empty
        {{-- Nothing: the menu always has its five groups. --}}
    @endforelse
</native:column>
