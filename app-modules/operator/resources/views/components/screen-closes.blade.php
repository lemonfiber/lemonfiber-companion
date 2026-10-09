@use('Modules\Stacks\Api\AStacksScreen')
@use('Modules\Wayfinding\Api\TheTabs')
{{-- Top-level for the reason the top bar is: hoisted out of the content tree,
     it becomes the platform's own navigation bar with its insets and gestures;
     left inside a container it is drawn as an ordinary row of buttons. --}}
{{-- A tab holding something new carries the count as text, and says it with
     the tab's name to a screen reader; one holding nothing carries no mark. --}}
<native:bottom-nav>
    @if ($marks->health->count > 0)
        <native:bottom-nav-item id="health" :active="$here === TheTabs::Health" label="{{ __(TheTabs::Health->said()) }}" url="{{ $goes->to(AStacksScreen::Health) }}" icon="{{ TheTabs::Health->glyph() }}" ios-icon="{{ TheTabs::Health->iosGlyph() }}" badge="{{ $marks->health->badge }}" badge-label="{{ trans_choice('news.new_on_tab', $marks->health->count, ['tab' => __(TheTabs::Health->said())]) }}" />
    @else
        <native:bottom-nav-item id="health" :active="$here === TheTabs::Health" label="{{ __(TheTabs::Health->said()) }}" url="{{ $goes->to(AStacksScreen::Health) }}" icon="{{ TheTabs::Health->glyph() }}" ios-icon="{{ TheTabs::Health->iosGlyph() }}" />
    @endif
    <native:bottom-nav-item id="services" :active="$here === TheTabs::Services" label="{{ __(TheTabs::Services->said()) }}" url="{{ $goes->to(AStacksScreen::Services) }}" icon="{{ TheTabs::Services->glyph() }}" ios-icon="{{ TheTabs::Services->iosGlyph() }}" />
    @if ($marks->updates->count > 0)
        <native:bottom-nav-item id="updates" :active="$here === TheTabs::Updates" label="{{ __(TheTabs::Updates->said()) }}" url="{{ $goes->to(AStacksScreen::Updates) }}" icon="{{ TheTabs::Updates->glyph() }}" ios-icon="{{ TheTabs::Updates->iosGlyph() }}" badge="{{ $marks->updates->badge }}" badge-label="{{ trans_choice('news.new_on_tab', $marks->updates->count, ['tab' => __(TheTabs::Updates->said())]) }}" />
    @else
        <native:bottom-nav-item id="updates" :active="$here === TheTabs::Updates" label="{{ __(TheTabs::Updates->said()) }}" url="{{ $goes->to(AStacksScreen::Updates) }}" icon="{{ TheTabs::Updates->glyph() }}" ios-icon="{{ TheTabs::Updates->iosGlyph() }}" />
    @endif
    <native:bottom-nav-item id="repairs" :active="$here === TheTabs::Repairs" label="{{ __(TheTabs::Repairs->said()) }}" url="{{ $goes->to(AStacksScreen::Repairs) }}" icon="{{ TheTabs::Repairs->glyph() }}" ios-icon="{{ TheTabs::Repairs->iosGlyph() }}" />
</native:bottom-nav>
