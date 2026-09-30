{{-- The side menu: the stack it is about and what is new, then its five
     groups, then the stack's settings and the app's. Every item is drawn on
     every stack. --}}
<native:column class="w-full gap-4 p-4">
    <x-design::title>{{ $stack->name()->shown() }}</x-design::title>

    <x-design::section>
        <x-design::row
            :headline="__($whatsNew->said())"
            :answers-to="__($whatsNew->said())"
            :goes="$whatsNew->goes()"
            :icon="$whatsNew->glyph()"
            :ios-icon="$whatsNew->iosGlyph()"
        />
    </x-design::section>

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

    <x-design::section>
        @forelse ($settings as $item)
            <x-design::row
                :headline="__($item->said())"
                :answers-to="__($item->said())"
                :goes="$item->goes()"
                :icon="$item->glyph()"
                :ios-icon="$item->iosGlyph()"
            />
        @empty
            {{-- Nothing: there are always the two settings. --}}
        @endforelse
    </x-design::section>
</native:column>
