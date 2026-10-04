{{-- The side menu: the stack it is about and the way to another stack, then
     what follows from whose session this phone holds for it, then the stack's
     settings and the app's. A member's menu adds what the stack owes them; the
     operator's adds what is new, counting how much is new here, and the five
     groups, every item on every stack.
     Until a session says whose it is, nothing is added. --}}
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

        {{-- What's new opens on this stack and counts how much is new here,
             said with its label to a screen reader; nothing new, no count. --}}
        @if ($rows->whatIsNew)
            <x-design::row
                :headline="__($whatsNew->said())"
                :answers-to="$whatsNew->count() > 0 ? trans_choice('news.new_on_tab', $whatsNew->count(), ['tab' => __($whatsNew->said())]) : __($whatsNew->said())"
                tap="openWhatsNew()"
                :badge="$whatsNew->badge()"
                :icon="$whatsNew->glyph()"
                :ios-icon="$whatsNew->iosGlyph()"
            />
        @endif

        @forelse ($rows->owed as $owed)
            <x-design::row
                :headline="__($owed->said())"
                :answers-to="__($owed->said())"
                :goes="$owed->screen()->forTheStack($stack->id())"
                :icon="$owed->glyph()"
                :ios-icon="$owed->iosGlyph()"
            />
        @empty
            {{-- Nothing: only a member is owed anything here. --}}
        @endforelse
    </x-design::section>

    @forelse ($rows->groups as $group)
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
        {{-- Nothing: the five groups are the operator's. --}}
    @endforelse

    <x-design::section>
        <x-design::row
            :headline="__($stackSettings->said())"
            :answers-to="__($stackSettings->said())"
            :goes="$stackSettings->screen()->forTheStack($stack->id())"
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
