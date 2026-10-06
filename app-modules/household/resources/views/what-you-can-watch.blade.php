<x-operator::screen-opens :title="__('household.tabs.home')" :back="$this->hasAWayBack()" />

<x-operator::content>

@if ($this->answer()->cameBack())
    {{-- The shelf as rows of posters: what came in most recently, then a row
         for each kind it holds. A poster carries its title, its kind and its
         year, and says all three to a screen reader at once. --}}
    @forelse ($this->answer()->rows as $row)
        <x-household::shelf-row :row="$row" />
    @empty
        {{-- Said in as many words. A member whose shelf holds nothing has an
             answer, and a blank frame is what a library nobody could reach
             looks like — the branch below is what keeps those apart, and this
             arm is only reached where the core answered. --}}
        <x-design::section>
            <x-design::row :headline="__('household.shelf_is_empty')" :supporting="__('household.shelf_is_empty_action')" />
        </x-design::section>
    @endforelse

    <x-design::action label="{{ __('household.ask_again') }}" tap="again()" tone="tonal" />
@elseif ($this->answer()->isOutOfReach)
    {{-- Not an empty shelf, and drawn so it can never be mistaken for one. The
         library exists and could not be reached, which is the opposite thing
         to tell somebody about their own collection.

         The core's own sentences, in the core's own words. Whoever could not
         reach what is a fact about two machines, and a line written here would
         be this app guessing at which. --}}
    <x-design::notice tone="unknown">
        <x-design::strong>{{ __('household.shelf_is_out_of_reach') }}</x-design::strong>
        @forelse ($this->answer()->reasons as $reason)
            <x-design::body>{{ $reason }}</x-design::body>
        @empty
            {{-- The core said it could not, and said nothing about why. Better than
                 a blank frame, which reads as the shelf being empty. --}}
            <x-design::body>{{ __('household.shelf_is_out_of_reach_action') }}</x-design::body>
        @endforelse
    </x-design::notice>

    <x-design::action label="{{ __('household.ask_again') }}" tap="again()" />
@elseif ($this->answer()->isSignedIn)
    {{-- What stood in the way and what to do about it, both off the obstacle,
         so this screen cannot describe a condition differently from the one
         beside it. --}}
    <x-design::notice tone="unknown">
        <x-design::strong>{{ __($this->answer()->met, $this->answer()->filling()) }}</x-design::strong>
        <x-design::body>{{ __($this->answer()->remedy, $this->answer()->filling()) }}</x-design::body>
    </x-design::notice>

    <x-design::action label="{{ __('household.ask_again') }}" tap="again()" />
    @if ($this->answer()->isPutRightInTheAppsSettings())
        <x-design::action label="{{ __('connection.open_settings') }}" tap="openTheAppsSettings()" />
        @if ($this->theSettingsWouldNotOpen)
            <x-design::note>{{ __('connection.settings_would_not_open') }}</x-design::note>
        @endif
    @endif
@else
    {{-- The session has ended, so nothing was asked and there is nothing to
         report. The remedy is a screen rather than a sentence. --}}
    <x-design::body>{{ __('connection.session_has_ended') }}</x-design::body>
    <x-design::action label="{{ __('connection.sign_in') }}" :goes="$this->signIn()" />
@endif
</x-operator::content>

<x-household::screen-closes :goes="$this->goes()" :here="$this->itsTab()" />
