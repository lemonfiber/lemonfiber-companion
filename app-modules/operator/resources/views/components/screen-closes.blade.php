{{-- Top-level for the reason the top bar is: hoisted out of the content tree,
     it becomes the platform's own navigation bar with its insets and gestures;
     left inside a container it is drawn as an ordinary row of buttons. --}}
{{-- A tab holding something new carries the count as text, and says it with
     the tab's name to a screen reader; one holding nothing carries no mark. --}}
<native:bottom-nav>
    @if ($marks->health->count > 0)
        <native:bottom-nav-item id="health" :active="$here?->value === 'health'" label="{{ __('navigation.health') }}" url="{{ $goes->health() }}" icon="home" badge="{{ $marks->health->badge }}" badge-label="{{ trans_choice('news.new_on_tab', $marks->health->count, ['tab' => __('navigation.health')]) }}" />
    @else
        <native:bottom-nav-item id="health" :active="$here?->value === 'health'" label="{{ __('navigation.health') }}" url="{{ $goes->health() }}" icon="home" />
    @endif
    <native:bottom-nav-item id="services" :active="$here?->value === 'services'" label="{{ __('navigation.services') }}" url="{{ $goes->services() }}" icon="apps" />
    @if ($marks->updates->count > 0)
        <native:bottom-nav-item id="updates" :active="$here?->value === 'updates'" label="{{ __('navigation.updates') }}" url="{{ $goes->updates() }}" icon="download" badge="{{ $marks->updates->badge }}" badge-label="{{ trans_choice('news.new_on_tab', $marks->updates->count, ['tab' => __('navigation.updates')]) }}" />
    @else
        <native:bottom-nav-item id="updates" :active="$here?->value === 'updates'" label="{{ __('navigation.updates') }}" url="{{ $goes->updates() }}" icon="download" />
    @endif
    <native:bottom-nav-item id="repairs" :active="$here?->value === 'repairs'" label="{{ __('navigation.repairs') }}" url="{{ $goes->repairs() }}" icon="build" />
</native:bottom-nav>
