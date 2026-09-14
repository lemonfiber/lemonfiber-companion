<native:column class="w-full gap-4 p-6">
    <native:text class="text-lg font-bold">{{ $this->stack()->name()->shown() }}</native:text>

    @unless ($this->isSignedIn())
        {{-- N1-R44: the session has ended, so nothing was asked. --}}
        <native:text>{{ __('connection.session_has_ended') }}</native:text>
        <native:button label="{{ __('connection.sign_in') }}" @navigate="{{ $this->signInAt() }}" />
    @elseif ($this->met() !== '')
        {{-- N1-R10: both sentences come off the obstacle, so this screen cannot
             describe a condition differently from the one next to it. --}}
        <native:text class="font-bold">{{ __($this->met()) }}</native:text>
        <native:text>{{ __($this->remedy()) }}</native:text>
    @elseif ($this->isWorkingItOut())
        {{-- N2-R7: the unconfirmed form is still a job, so this is a real state
             rather than a spinner. Said plainly, with the asking left to the
             operator — N1-R17 keeps a screen from being a poller. --}}
        <native:text class="font-bold">{{ __('health.working_it_out') }}</native:text>
        <native:text>{{ __('health.working_it_out_action') }}</native:text>
        <native:button label="{{ __('health.ask_again') }}" @tap="again()" />
    @elseif ($this->hasEnded())
        {{-- The stack has no outcome for that asking any more. Not a fault and
             not an answer: nothing was carried out, and the way forward is to
             ask again from the start. Saying so is what keeps it from reading
             as a machine that is broken. --}}
        <native:text class="font-bold">{{ __('health.nothing_came_back') }}</native:text>
        <native:text>{{ __('health.nothing_came_back_action') }}</native:text>
        <native:button label="{{ __('health.ask_again') }}" @tap="again()" />
    @else
        @forelse ($this->repairs() as $repair)
            <native:column class="w-full gap-1">
                {{-- N2-R4: all three clauses, in the order the requirement puts
                     them, and before anything asks for a yes. What it does, what
                     else it touches, and whether it can be taken back. --}}
                <native:text class="font-bold">{{ $repair->does }}</native:text>

                @forelse ($repair->effects as $effect)
                    <native:text class="text-sm">{{ $effect }}</native:text>
                @empty
                    <native:text class="text-sm">{{ __('health.affects_nothing_else') }}</native:text>
                @endforelse

                <native:text>{{ __($repair->undoing) }}</native:text>
            </native:column>
        @empty
            {{-- A stack with nothing to put right is the healthy case, and it is
                 told apart from a job that ended: there is nothing to fix, which
                 is an answer, rather than ask me again. --}}
            <native:text class="font-bold">{{ __('health.nothing_to_put_right') }}</native:text>
        @endforelse

        <native:button label="{{ __('health.ask_again') }}" @tap="again()" />
    @endunless

    <native:button label="{{ __('health.back_to_the_stack') }}" @navigate="{{ $this->healthIsAt() }}" />
</native:column>
