<x-operator::screen-opens :title="__('household.tabs.requests')" :back="$this->hasAWayBack()" />

<x-operator::content>

@if ($this->answer()->cameBack())
    {{-- The core's own sentences, in the core's own order and wording.
         Rendered rather than translated: the household's rules are the core's
         to state, and putting them through this app's catalogue would mean
         inventing a line for a policy it has never heard of. --}}
    <x-design::card>
        @forelse ($this->answer()->sentences as $sentence)
            <x-design::body>{{ $sentence }}</x-design::body>
        @empty
            {{-- Said in as many words, because the alternative is a blank frame —
                 and a blank frame is what a stack that would not say looks like.
                 The two are opposite sentences to whoever is reading them, and the
                 branch above is what keeps them apart: this arm is only reached
                 where the stack answered. --}}
            <x-design::strong>{{ __('household.nothing_owed') }}</x-design::strong>
            <x-design::body>{{ __('household.nothing_owed_action') }}</x-design::body>
        @endforelse
    </x-design::card>

    {{-- What they have asked this machine for, beneath what they are owed:
         the sentences say what happens when they ask, and this says what
         became of the times they did. --}}
    <x-design::heading>{{ __('household.your_requests') }}</x-design::heading>

    @if ($this->requests()->cameBack())
        @forelse ($this->requests()->rows as $request)
            <x-design::card>
                <x-design::strong>{{ $request->title }}</x-design::strong>

                {{-- The state in the member's words rather than the operator's, and
                     a state rather than a stage: no queue position, no percentage,
                     no service doing the fetching. None of it is an answer to
                     somebody waiting for a film. --}}
                <x-design::note>{{ __($request->standing) }}</x-design::note>

                @if ($request->wasRefused())
                    {{-- The reason the stack gave, shown as it was given. A refusal
                         a member is told about without the reason is a screen
                         asking them to go and find somebody. --}}
                    <x-design::body>{{ $request->reason }}</x-design::body>
                @endif
            </x-design::card>
        @empty
            {{-- Said in as many words, for the reason the arm above it is: a
                 blank space is what a stack that would not say looks like, and
                 having asked for nothing is an ordinary thing to have done. --}}
            <x-design::body>{{ __('household.nothing_asked_for') }}</x-design::body>
        @endforelse
    @else
        {{-- Both readings are one call to one endpoint, so this is usually the
             obstacle already drawn above rather than a second one. It is said
             here anyway: the two are parsed separately, and a stack whose
             sentences this app could read while its requests it could not
             would otherwise leave this section silently empty — which reads as
             having asked for nothing. --}}
        <x-design::notice tone="unknown">
            <x-design::strong>{{ __($this->requests()->met, $this->requests()->filling()) }}</x-design::strong>
        </x-design::notice>
    @endif

    {{-- Tonal, because the reading is what this frame is for. A filled bar
         would make asking again look like the thing to do. --}}
    <x-design::action label="{{ __('household.ask_again') }}" tap="askAgain()" tone="tonal" />
@elseif ($this->answer()->isSignedIn)
    {{-- What stood in the way and what to do about it, both off the obstacle,
         so this screen cannot describe a condition differently from the one
         beside it. A refusal is drawn as a refusal here: an account that may
         not ask for something is told so, rather than shown an empty list. --}}
    <x-design::notice tone="unknown">
        <x-design::strong>{{ __($this->answer()->met, $this->answer()->filling()) }}</x-design::strong>
        <x-design::body>{{ __($this->answer()->remedy, $this->answer()->filling()) }}</x-design::body>
    </x-design::notice>

    {{-- The action is offered and the failure reported, rather than taken away
         because the stack is unreachable. Without it the only way back is
         leaving and returning. --}}
    <x-design::action label="{{ __('household.ask_again') }}" tap="askAgain()" />
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
