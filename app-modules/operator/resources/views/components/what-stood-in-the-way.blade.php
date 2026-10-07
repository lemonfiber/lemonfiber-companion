@use('Modules\Stacks\Api\AStacksScreen')
{{-- Drawn only where a reading did not come back, which is the arm of the
     screen's own `cameBack()` that says so. There is no guard here: a second
     one would be a second expression of the same rule, and two expressions are
     how a screen comes to draw both arms or neither.

     No container of its own: the lines stand in the column they are drawn in. --}}
@if ($went->isSignedIn)
    {{-- What stood in the way and what to do about it, both off the obstacle —
         so no screen describes a condition differently from the one beside
         it. --}}
    <x-operator::emphasis>{{ __($went->met, $went->filling()) }}</x-operator::emphasis>
    <x-design::body>{{ __($went->remedy, $went->filling()) }}</x-design::body>

    {{-- The address that was tried, where the stack was not reached: a stale
         or wrong one is often the whole story, and the operator cannot see
         that without seeing it. Drawn here and on no member's screen. --}}
    @if ($went->whereItWasTried() !== '')
        <x-operator::note>{{ __('connection.tried_at', ['address' => $went->whereItWasTried()]) }}</x-operator::note>
    @endif

    {{-- The action is offered and the failure reported, rather than taken away
         because the stack is unreachable. Without it the only way back is
         leaving and returning, which is named separately as what a screen must
         not rely on. A screen that offers asking again in the column already
         hands nothing here, so the column holds one. --}}
    @if ($askAgain !== '')
        <x-operator::action label="{{ __('health.ask_again') }}" :tap="$askAgain" />
    @endif

    {{-- Where the stack is too old for what was asked, the road to its
         updates, which is what would provide it. --}}
    @if ($went->isProvidedByAnUpdate())
        <x-operator::action label="{{ __('connection.go_to_updates') }}" :goes="$goes->to(AStacksScreen::Updates)" />
    @endif

    {{-- Beside asking again, where the remedy is a switch in the phone's
         settings: one tap to the switch rather than a hunt for it. --}}
    @if ($went->isPutRightInTheAppsSettings())
        <x-operator::action label="{{ __('connection.open_settings') }}" tap="openTheAppsSettings()" />
        @if ($settingsWouldNotOpen)
            <x-operator::note>{{ __('connection.settings_would_not_open') }}</x-operator::note>
        @endif
    @endif
@else
    {{-- The session has ended, so nothing was asked and there is nothing to
         report. The remedy is a screen rather than a sentence. --}}
    <x-design::body>{{ __('connection.session_has_ended') }}</x-design::body>
    <x-operator::action label="{{ __('connection.sign_in') }}" :goes="$goes->to(AStacksScreen::SignIn)" />
@endif
