<x-operator::screen-opens :title="__('household.tabs.search')" :back="$this->hasAWayBack()" />

<x-operator::content>
    {{-- Said as a fact about this version of the app, in household words, and
         with where the member's titles already are. --}}
    <x-design::strong>{{ __('household.search_is_coming') }}</x-design::strong>
    <x-design::body>{{ __('household.search_is_coming_action') }}</x-design::body>
</x-operator::content>

<x-household::screen-closes :goes="$this->goes()" :here="$this->itsTab()" />
