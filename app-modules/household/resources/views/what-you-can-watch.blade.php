<native:top-bar title="{{ __('household.shelf') }}" />

@if ($this->answer()->cameBack())
<native:column class="w-full gap-4 px-6 py-4">
    @forelse ($this->answer()->holdings as $holding)
        <native:column class="w-full gap-1">
            <native:text class="font-bold">{{ $holding->titled }}</native:text>

            {{-- The kind, and the year where the core had one. Drawn under the
                 title rather than beside it: a row that runs out of width puts
                 the title second, and the title is the part somebody is
                 scanning for. --}}
            <native:text class="text-sm">
                {{ __($holding->medium) }}@if ($holding->year !== '') · {{ $holding->year }}@endif
            </native:text>
        </native:column>

        @unless ($loop->last)
            <native:divider />
        @endunless
    @empty
        {{-- Said in as many words. A member whose shelf holds nothing has an
             answer, and a blank frame is what a library nobody could reach
             looks like — the branch above is what keeps those apart, and this
             arm is only reached where the core answered. --}}
        <native:text class="font-bold">{{ __('household.shelf_is_empty') }}</native:text>
        <native:text>{{ __('household.shelf_is_empty_action') }}</native:text>
    @endforelse

    <native:pressable native:key="ask-again" class="w-full min-h-12 justify-center py-2" @press="again()" a11y-label="{{ __('household.ask_again') }}" :press-opacity="0.6">
        <native:text class="text-sm">{{ __('household.ask_again') }}</native:text>
    </native:pressable>

    <native:pressable native:key="back-to-the-machine" class="w-full min-h-12 justify-center py-2" @navigate="$this->health()" a11y-label="{{ __('household.back_to_the_machine') }}" :press-opacity="0.6">
        <native:text class="text-sm">{{ __('household.back_to_the_machine') }}</native:text>
    </native:pressable>
</native:column>
@elseif ($this->answer()->isOutOfReach)
<native:column class="w-full gap-4 px-6 py-4">
    {{-- Not an empty shelf, and drawn so it can never be mistaken for one. The
         library exists and could not be reached, which is the opposite thing
         to tell somebody about their own collection. --}}
    <native:text class="font-bold">{{ __('household.shelf_is_out_of_reach') }}</native:text>

    {{-- The core's own sentences, in the core's own words. Whoever could not
         reach what is a fact about two machines, and a line written here would
         be this app guessing at which. --}}
    @forelse ($this->answer()->reasons as $reason)
        <native:text>{{ $reason }}</native:text>
    @empty
        {{-- The core said it could not, and said nothing about why. Better than
             a blank frame, which reads as the shelf being empty. --}}
        <native:text>{{ __('household.shelf_is_out_of_reach_action') }}</native:text>
    @endforelse

    <native:button native:key="ask-again" class="w-full" label="{{ __('household.ask_again') }}" a11y-label="{{ __('household.ask_again') }}" @tap="again()" />

    <native:pressable native:key="back-to-the-machine" class="w-full min-h-12 justify-center py-2" @navigate="$this->health()" a11y-label="{{ __('household.back_to_the_machine') }}" :press-opacity="0.6">
        <native:text class="text-sm">{{ __('household.back_to_the_machine') }}</native:text>
    </native:pressable>
</native:column>
@elseif ($this->answer()->isSignedIn)
<native:column class="w-full gap-4 px-6 py-4">
    {{-- What stood in the way and what to do about it, both off the obstacle,
         so this screen cannot describe a condition differently from the one
         beside it. --}}
    <native:text class="font-bold">{{ __($this->answer()->met) }}</native:text>
    <native:text>{{ __($this->answer()->remedy) }}</native:text>

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
