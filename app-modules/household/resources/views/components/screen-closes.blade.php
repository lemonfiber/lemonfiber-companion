@use('Modules\Stacks\Api\AStacksScreen')
@use('Modules\Wayfinding\Api\TheHouseholdsTabs')
{{-- Top-level for the reason the top bar is: hoisted out of the content tree,
     it becomes the platform's own navigation bar with its insets and gestures.
     Each item names the screen it opens, in the order the member's tabs run. --}}
<native:bottom-nav>
    <native:bottom-nav-item id="home" :active="$here === TheHouseholdsTabs::Home" label="{{ __(TheHouseholdsTabs::Home->said()) }}" url="{{ $goes->to(AStacksScreen::Shelf) }}" icon="{{ TheHouseholdsTabs::Home->glyph() }}" ios-icon="{{ TheHouseholdsTabs::Home->iosGlyph() }}" />
    <native:bottom-nav-item id="search" :active="$here === TheHouseholdsTabs::Search" label="{{ __(TheHouseholdsTabs::Search->said()) }}" url="{{ $goes->to(AStacksScreen::Search) }}" icon="{{ TheHouseholdsTabs::Search->glyph() }}" ios-icon="{{ TheHouseholdsTabs::Search->iosGlyph() }}" />
    <native:bottom-nav-item id="requests" :active="$here === TheHouseholdsTabs::Requests" label="{{ __(TheHouseholdsTabs::Requests->said()) }}" url="{{ $goes->to(AStacksScreen::Owed) }}" icon="{{ TheHouseholdsTabs::Requests->glyph() }}" ios-icon="{{ TheHouseholdsTabs::Requests->iosGlyph() }}" />
    <native:bottom-nav-item id="profile" :active="$here === TheHouseholdsTabs::Profile" label="{{ __(TheHouseholdsTabs::Profile->said()) }}" url="{{ $goes->to(AStacksScreen::Profile) }}" icon="{{ TheHouseholdsTabs::Profile->glyph() }}" ios-icon="{{ TheHouseholdsTabs::Profile->iosGlyph() }}" />
</native:bottom-nav>
