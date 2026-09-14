<native:column class="w-full gap-4 p-6">
    <native:text class="text-lg font-bold">{{ $this->stack()->name()->shown() }}</native:text>

    @unless ($this->answer()->isSignedIn)
        {{-- N1-R44: the session has ended, so nothing was asked and there is
             nothing to report. The remedy is a screen rather than a sentence. --}}
        <native:text>{{ __('connection.session_has_ended') }}</native:text>
        <native:button
            label="{{ __('connection.sign_in') }}"
            @navigate="{{ $this->goes()->signIn() }}"
        />
    @elseif ($this->answer()->met !== '')
        {{-- N1-R10: what stood in the way, and what to do about it. Both come
             off the obstacle, so this screen cannot describe a condition
             differently from the one next to it. --}}
        <native:text class="font-bold">{{ __($this->answer()->met) }}</native:text>
        <native:text>{{ __($this->answer()->remedy) }}</native:text>

        {{-- N1-R3: the action is offered and the failure is reported, rather
             than the action being taken away because the stack is unreachable. --}}
        <native:button label="{{ __('health.ask_again') }}" @tap="again()" />
    @elseif ($this->asking() !== null)
        {{-- N2-R8: what this will take away, stated before the yes and not
             after it. A screen of its own rather than a line beside the row,
             because a confirmation an operator can tap past without reading is
             the same as no confirmation. --}}
        <native:text class="text-lg font-bold">
            {{ __('health.about_to', ['what' => $this->asking()->named()]) }}
        </native:text>
        <native:text>{{ __($this->asking()->doing()->saidOnTheScreen()) }}</native:text>

        {{-- N2-R8: how long for, as the stack reported it. Said here because
             this is the moment it is any use: *a second* and *three minutes*
             are different decisions, and the decision is made before the verb
             runs. N2-R14 is why there is no fallback sentence — a length this
             app invented would be a guess at something the stack knows, wrong
             in exactly the cases somebody most needs it, and wrong silently. --}}
        <native:text class="text-sm">
            {{ __($this->whatItTakesAway()->said, ['seconds' => $this->whatItTakesAway()->seconds]) }}
        </native:text>

        @if ($this->asking()->isAboutAForm())
            {{-- A whole form is every service in it, which is more than the
                 operator picked and has to be said as such. --}}
            <native:text>{{ __('health.about_to_form') }}</native:text>
        @else
            @if ($this->aboutTheService()?->wouldNotHelp)
                {{-- The honest statement for a service that is already being
                     started over and over: another restart adds a start to a
                     queue of starts. --}}
                <native:text class="font-bold">{{ __('health.would_not_help') }}</native:text>
            @endif

            @if (count($this->aboutTheService()?->leaning ?? []) > 0)
                {{-- What will not work without it. The part of `N2-R8` an
                     operator cannot work out from the row they tapped. --}}
                <native:text>{{ __('health.leaning_on_it') }}</native:text>

                @forelse ($this->aboutTheService()->leaning as $name)
                    <native:text class="text-sm">{{ $name }}</native:text>
                @empty
                    {{-- Unreachable while the branch above guards it, and
                         written anyway: `F6` wants the empty case to be the
                         same edit as the loop, so that removing the guard
                         cannot silently turn *nothing depends on it* into a
                         blank space. --}}
                    <native:text class="text-sm">{{ __('health.nothing_leans_on_it') }}</native:text>
                @endforelse
            @else
                <native:text>{{ __('health.nothing_leans_on_it') }}</native:text>
            @endif
        @endif

        <native:button label="{{ __('health.go_ahead') }}" @tap="agree()" />
        <native:button label="{{ __('health.never_mind') }}" @tap="neverMind()" />
    @else
        {{-- What it all amounts to, as the stack judged it — said before the
             rows, so an operator who opened this because a film would not play
             reads the answer before the list. --}}
        <native:text class="font-bold">{{ __($this->answer()->overall) }}</native:text>

        @if ($this->answer()->isSettling)
            {{-- N1-R27: something here becomes something else on its own, and
                 this says how often the screen looks. A screen that refreshes
                 silently is one an operator cannot reason about. --}}
            <native:text class="text-sm">{{ __($this->cadence()->saidOnTheScreen()) }}</native:text>
        @endif

        @forelse ($this->answer()->services as $service)
            <native:column class="w-full gap-1">
                <native:text class="font-bold">{{ $service->name }}</native:text>
                <native:text>{{ __($service->runsSaid) }}</native:text>
                <native:text class="text-sm">
                    {{ __('health.in_form', ['form' => $service->form]) }}
                </native:text>
                <native:text class="text-sm">{{ __($service->mattersSaid) }}</native:text>

                @if ($service->exited !== '')
                    {{-- What it ended with. A service that is running has no
                         code at all, so this line appears only where there is
                         one — an empty field and a zero are different facts. --}}
                    <native:text class="text-sm">
                        {{ __('health.it_exited', ['code' => $service->exited]) }}
                    </native:text>
                @endif

                @if ($service->isOurs)
                    {{-- N2-R7: the three verbs, offered per service. Not drawn
                         for something this stack does not run: a verb about a
                         host-managed service would be refused by the machine,
                         and offering it teaches an operator that the buttons
                         here are a guess. --}}
                    <native:button
                        label="{{ __('health.do.start') }}"
                        @tap="wouldYouLike('start', '{{ $service->id->named() }}')"
                    />
                    <native:button
                        label="{{ __('health.do.stop') }}"
                        @tap="wouldYouLike('stop', '{{ $service->id->named() }}')"
                    />
                    <native:button
                        label="{{ __('health.do.restart') }}"
                        @tap="wouldYouLike('restart', '{{ $service->id->named() }}')"
                    />
                @else
                    <native:text class="text-sm">{{ __('health.host_runs_it') }}</native:text>
                @endif

                <native:button
                    label="{{ __('health.read_its_logs') }}"
                    @navigate="{{ $this->goes()->logsOf($service->id) }}"
                />
            </native:column>
        @empty
            {{-- Not the same screen as a stack that could not be asked. Nothing
                 running is the state the operator came here to change, and
                 saying so is what tells it apart from the obstacle branch. --}}
            <native:text class="font-bold">{{ __('health.nothing_is_running') }}</native:text>
        @endforelse

        {{-- N2-R7's other granularity. The forms come from the stack's own list
             rather than from the rows, so the form an operator opened this
             screen to start — the one with nothing running in it — is here. --}}
        <native:text class="font-bold">{{ __('health.by_form') }}</native:text>

        @forelse ($this->answer()->forms as $form)
            <native:column class="w-full gap-1">
                <native:text>{{ $form }}</native:text>
                <native:button
                    label="{{ __('health.do.start') }}"
                    @tap="wouldYouLike('start', '{{ $form }}')"
                />
                <native:button
                    label="{{ __('health.do.stop') }}"
                    @tap="wouldYouLike('stop', '{{ $form }}')"
                />
                <native:button
                    label="{{ __('health.do.restart') }}"
                    @tap="wouldYouLike('restart', '{{ $form }}')"
                />
            </native:column>
        @empty
            {{-- A stack with no forms at all is a machine nothing has been set
                 up on, which is not the same as one whose forms are all
                 stopped — and it is not something *ask again* fixes. --}}
            <native:text>{{ __('health.no_forms_at_all') }}</native:text>
        @endforelse

        <native:button label="{{ __('health.ask_again') }}" @tap="again()" />
    @endunless

    <native:button label="{{ __('health.back_to_the_stack') }}" @navigate="{{ $this->goes()->health() }}" />
</native:column>
