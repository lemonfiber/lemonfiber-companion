<native:top-bar title="{{ __('household.yours') }}" />

@if ($this->answer()->cameBack())
<native:column class="w-full gap-4 px-6 py-4">
    {{-- The core's own sentences, in the core's own order and wording.
         Rendered rather than translated: the household's rules are the core's
         to state, and putting them through this app's catalogue would mean
         inventing a line for a policy it has never heard of. --}}
    @forelse ($this->answer()->sentences as $sentence)
        <native:text>{{ $sentence }}</native:text>

        @unless ($loop->last)
            <native:divider />
        @endunless
    @empty
        {{-- Said in as many words, because the alternative is a blank frame —
             and a blank frame is what a stack that would not say looks like.
             The two are opposite sentences to whoever is reading them, and the
             branch above is what keeps them apart: this arm is only reached
             where the stack answered. --}}
        <native:text class="font-bold">{{ __('household.nothing_owed') }}</native:text>
        <native:text>{{ __('household.nothing_owed_action') }}</native:text>
    @endforelse

    {{-- What they have asked this machine for, beneath what they are owed:
         the sentences say what happens when they ask, and this says what
         became of the times they did. --}}
    <native:divider />
    <native:text class="font-bold">{{ __('household.your_requests') }}</native:text>

    @if ($this->requests()->cameBack())
        @forelse ($this->requests()->rows as $request)
            <native:text>{{ $request->title }}</native:text>

            {{-- The state in the member's words rather than the operator's, and
                 a state rather than a stage: no queue position, no percentage,
                 no service doing the fetching. None of it is an answer to
                 somebody waiting for a film. --}}
            <native:text class="text-sm">{{ __($request->standing) }}</native:text>

            @if ($request->wasRefused())
                {{-- The reason the stack gave, shown as it was given. A refusal
                     a member is told about without the reason is a screen
                     asking them to go and find somebody. --}}
                <native:text class="text-sm">{{ $request->reason }}</native:text>
            @endif

            @unless ($loop->last)
                <native:divider />
            @endunless
        @empty
            {{-- Said in as many words, for the reason the arm above it is: a
                 blank space is what a stack that would not say looks like, and
                 having asked for nothing is an ordinary thing to have done. --}}
            <native:text>{{ __('household.nothing_asked_for') }}</native:text>
        @endforelse
    @else
        {{-- Both readings are one call to one endpoint, so this is usually the
             obstacle already drawn above rather than a second one. It is said
             here anyway: the two are parsed separately, and a stack whose
             sentences this app could read while its requests it could not
             would otherwise leave this section silently empty — which reads as
             having asked for nothing. --}}
        <native:text class="text-sm">{{ __($this->requests()->met) }}</native:text>
    @endif

    {{-- Quiet, because the reading is what this frame is for. A filled bar
         would make asking again look like the thing to do. --}}
    <native:pressable native:key="ask-again" class="w-full min-h-12 justify-center py-2" @press="again()" a11y-label="{{ __('household.ask_again') }}" :press-opacity="0.6">
        <native:text class="text-sm">{{ __('household.ask_again') }}</native:text>
    </native:pressable>

    {{-- The way back to the machine this reading is about. A screen under a
         machine ends with one: going deeper is not a way out. --}}
    <native:pressable native:key="back-to-the-machine" class="w-full min-h-12 justify-center py-2" @navigate="$this->health()" a11y-label="{{ __('household.back_to_the_machine') }}" :press-opacity="0.6">
        <native:text class="text-sm">{{ __('household.back_to_the_machine') }}</native:text>
    </native:pressable>
</native:column>
@elseif ($this->answer()->isSignedIn)
<native:column class="w-full gap-4 px-6 py-4">
    {{-- What stood in the way and what to do about it, both off the obstacle,
         so this screen cannot describe a condition differently from the one
         beside it. A refusal is drawn as a refusal here: an account that may
         not ask for something is told so, rather than shown an empty list. --}}
    <native:text class="font-bold">{{ __($this->answer()->met) }}</native:text>
    <native:text>{{ __($this->answer()->remedy) }}</native:text>

    {{-- The action is offered and the failure reported, rather than taken away
         because the stack is unreachable. Without it the only way back is
         leaving and returning. --}}
    <native:button native:key="ask-again" class="w-full" label="{{ __('household.ask_again') }}" a11y-label="{{ __('household.ask_again') }}" @tap="again()" />

    <native:pressable native:key="back-to-the-machine" class="w-full min-h-12 justify-center py-2" @navigate="$this->health()" a11y-label="{{ __('household.back_to_the_machine') }}" :press-opacity="0.6">
        <native:text class="text-sm">{{ __('household.back_to_the_machine') }}</native:text>
    </native:pressable>
</native:column>
@else
<native:column class="w-full gap-4 px-6 py-4">
    {{-- The session has ended, so nothing was asked and there is nothing to
         report. The remedy is a screen rather than a sentence. --}}
    <native:text>{{ __('connection.session_has_ended') }}</native:text>
    <native:button native:key="sign-in" class="w-full" label="{{ __('connection.sign_in') }}" a11y-label="{{ __('connection.sign_in') }}" @navigate="$this->signIn()" />

    <native:pressable native:key="back-to-the-machine" class="w-full min-h-12 justify-center py-2" @navigate="$this->health()" a11y-label="{{ __('household.back_to_the_machine') }}" :press-opacity="0.6">
        <native:text class="text-sm">{{ __('household.back_to_the_machine') }}</native:text>
    </native:pressable>
</native:column>
@endif
