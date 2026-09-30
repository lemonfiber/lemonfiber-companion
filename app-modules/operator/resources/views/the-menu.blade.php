{{-- The side menu: the stack it is about, the way to another stack and what
     is new, then its five groups, then the stack's settings and the app's.
     Every item is drawn on every stack. --}}
<native:column class="w-full gap-4 p-4">
    <x-design::title>{{ $stack->name()->shown() }}</x-design::title>

    <x-design::section>
        <x-design::row
            :headline="__('navigation.menu.switch_stack')"
            :answers-to="__('navigation.menu.switch_stack')"
            tap="chooseAStack()"
            icon="swap_horiz"
            ios-icon="arrow.left.arrow.right"
        />

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
        <x-design::row
            :headline="__($stackSettings->said())"
            :answers-to="__($stackSettings->said())"
            :goes="$stackSettings->goes()"
            :icon="$stackSettings->glyph()"
            :ios-icon="$stackSettings->iosGlyph()"
        />
        <x-design::row
            :headline="__($appSettings->said())"
            :answers-to="__($appSettings->said())"
            :goes="$appSettings->appSettings()"
            :icon="$appSettings->glyph()"
            :ios-icon="$appSettings->iosGlyph()"
        />
    </x-design::section>
</native:column>
