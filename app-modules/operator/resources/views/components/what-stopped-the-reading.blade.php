{{-- Drawn only where a reading did not come back, which is the `@else` of the
     screen's own `cameBack()`. There is no guard here: a second one would be a
     second expression of the same rule, and two expressions are how a screen
     comes to draw both arms or neither. --}}
@if ($went->isSignedIn)
    <x-operator::content>
        {{-- What stood in the way and what to do about it, both off the
             obstacle — so no screen describes a condition differently from the
             one beside it. --}}
        <x-operator::emphasis>{{ __($went->met) }}</x-operator::emphasis>
        <native:text>{{ __($went->remedy) }}</native:text>

        {{-- The action is offered and the failure reported, rather than taken
             away because the stack is unreachable. Without it the only way back
             is leaving and returning, which `N1-R27` names separately as what a
             screen must not rely on. --}}
        <x-operator::action label="{{ __('health.ask_again') }}" tap="{{ $askAgain }}" />
    </x-operator::content>
@else
    <x-operator::content>
        {{-- The session has ended, so nothing was asked and there is nothing to
             report. The remedy is a screen rather than a sentence. --}}
        <native:text>{{ __('connection.session_has_ended') }}</native:text>
        <x-operator::action label="{{ __('connection.sign_in') }}" :goes="$signInGoesTo" />
    </x-operator::content>
@endif
