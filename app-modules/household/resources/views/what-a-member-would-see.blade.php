@use('Modules\Wayfinding\Api\TheHouseholdsTabs')
<x-operator::screen-opens :title="__($this->tab()->said())" :back="$this->hasAWayBack()" />

<x-operator::content>

<x-household::the-preview-mark />

{{-- The member's two tabs this preview shows, as a choice on the one screen,
     so a step back from either is the operator's screen it was opened from. --}}
<x-design::chips>
    <x-design::chip label="{{ __(TheHouseholdsTabs::Home->said()) }}" tap="showHome()" :chosen="$this->tab() === TheHouseholdsTabs::Home" />
    <x-design::chip label="{{ __(TheHouseholdsTabs::Requests->said()) }}" tap="showRequests()" :chosen="$this->tab() === TheHouseholdsTabs::Requests" />
</x-design::chips>

@if ($this->tab() === TheHouseholdsTabs::Home)
    @if ($this->shelf()->cameBack())
        {{-- The Home a member draws, from the core's answer for the
             household's defaults: nobody's library, so no name, no history
             and none of their own requests. --}}
        @if ($this->shelf()->hasAHero())
            <x-household::hero :poster="$this->shelf()->hero" />
        @endif
        @forelse ($this->shelf()->rows as $row)
            <x-household::shelf-row :row="$row" />
        @empty
            <x-design::section>
                <x-design::row :headline="__('household.shelf_is_empty')" :supporting="__('household.shelf_is_empty_action')" />
            </x-design::section>
        @endforelse

        <x-design::action label="{{ __('household.ask_again') }}" tap="again()" tone="tonal" />
    @elseif ($this->shelf()->isOutOfReach)
        {{-- Not an empty shelf, and drawn so it can never be mistaken for one. --}}
        <x-design::notice tone="unknown">
            <x-design::strong>{{ __('household.shelf_is_out_of_reach') }}</x-design::strong>
            @forelse ($this->shelf()->reasons as $reason)
                <x-design::body>{{ $reason }}</x-design::body>
            @empty
                <x-design::body>{{ __('household.shelf_is_out_of_reach_action') }}</x-design::body>
            @endforelse
        </x-design::notice>

        <x-design::action label="{{ __('household.ask_again') }}" tap="again()" />
    @elseif ($this->shelf()->isSignedIn)
        <x-design::notice tone="unknown">
            <x-design::strong>{{ __($this->shelf()->met, $this->shelf()->filling()) }}</x-design::strong>
            <x-design::body>{{ __($this->shelf()->remedy, $this->shelf()->filling()) }}</x-design::body>
        </x-design::notice>

        <x-design::action label="{{ __('household.ask_again') }}" tap="again()" />
        @if ($this->shelf()->isPutRightInTheAppsSettings())
            <x-design::action label="{{ __('connection.open_settings') }}" tap="openTheAppsSettings()" />
            @if ($this->theSettingsWouldNotOpen)
                <x-design::note>{{ __('connection.settings_would_not_open') }}</x-design::note>
            @endif
        @endif
    @else
        {{-- The operator's session has ended, so nothing was asked. --}}
        <x-design::body>{{ __('connection.session_has_ended') }}</x-design::body>
        <x-design::action label="{{ __('connection.sign_in') }}" :goes="$this->signIn()" />
    @endif
@elseif ($this->allowance()->cameBack())
    {{-- The core's own sentences for the household's defaults, rendered as a
         member's are and never composed here. --}}
    <x-design::card>
        @forelse ($this->allowance()->sentences as $sentence)
            <x-design::body>{{ $sentence }}</x-design::body>
        @empty
            <x-design::strong>{{ __('household.nothing_owed') }}</x-design::strong>
            <x-design::body>{{ __('household.nothing_owed_action') }}</x-design::body>
        @endforelse
    </x-design::card>

    {{-- Drawn where a member would ask, and unusable: asking from a preview
         would be a real request made under the operator. --}}
    <x-design::action label="{{ __('household.preview.ask') }}" :disabled="true" />
    <x-design::note>{{ __('household.preview.cannot_ask') }}</x-design::note>

    <x-design::action label="{{ __('household.ask_again') }}" tap="again()" tone="tonal" />
@elseif ($this->allowance()->isSignedIn)
    <x-design::notice tone="unknown">
        <x-design::strong>{{ __($this->allowance()->met, $this->allowance()->filling()) }}</x-design::strong>
        <x-design::body>{{ __($this->allowance()->remedy, $this->allowance()->filling()) }}</x-design::body>
    </x-design::notice>

    <x-design::action label="{{ __('household.ask_again') }}" tap="again()" />
    @if ($this->allowance()->isPutRightInTheAppsSettings())
        <x-design::action label="{{ __('connection.open_settings') }}" tap="openTheAppsSettings()" />
        @if ($this->theSettingsWouldNotOpen)
            <x-design::note>{{ __('connection.settings_would_not_open') }}</x-design::note>
        @endif
    @endif
@else
    {{-- The operator's session has ended, so nothing was asked. --}}
    <x-design::body>{{ __('connection.session_has_ended') }}</x-design::body>
    <x-design::action label="{{ __('connection.sign_in') }}" :goes="$this->signIn()" />
@endif
</x-operator::content>
