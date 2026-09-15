<x-operator::screen
    :title="$this->stack()->name()->shown()"
    :signed-in="$this->answer()->isSignedIn"
    :met="$this->answer()->met"
    :remedy="$this->answer()->remedy"
    :sign-in-goes-to="$this->goes()->signIn()"
    :goes="$this->goes()"
    here="services"
>
@if ($this->asking() !== null)
    {{-- N2-R8: what this will take away, stated before the yes and not
         after it. A screen of its own rather than a line beside the row,
         because a confirmation an operator can tap past without reading is
         the same as no confirmation. --}}
    <x-operator::heading>
        {{ __('health.about_to', ['what' => $this->asking()->named()]) }}
    </x-operator::heading>
    <native:text>{{ __($this->asking()->doing()->saidOnTheScreen()) }}</native:text>

    {{-- N2-R8: how long for, as the stack reported it. Said here because
         this is the moment it is any use: *a second* and *three minutes*
         are different decisions, and the decision is made before the verb
         runs. N2-R14 is why there is no fallback sentence — a length this
         app invented would be a guess at something the stack knows, wrong
         in exactly the cases somebody most needs it, and wrong silently. --}}
    <x-operator::note>
        {{ __($this->whatItTakesAway()->said, ['seconds' => $this->whatItTakesAway()->seconds]) }}
    </x-operator::note>

    @if ($this->asking()->isAboutAForm())
        {{-- A whole form is every service in it, which is more than the
             operator picked and has to be said as such. --}}
        <native:text>{{ __('health.about_to_form') }}</native:text>
    @else
        @if ($this->aboutTheService()?->wouldNotHelp)
            {{-- The honest statement for a service that is already being
                 started over and over: another restart adds a start to a
                 queue of starts. --}}
            <x-operator::emphasis>{{ __('health.would_not_help') }}</x-operator::emphasis>
        @endif

        @if (count($this->aboutTheService()?->leaning ?? []) > 0)
            {{-- What will not work without it. The part of `N2-R8` an
                 operator cannot work out from the row they tapped. --}}
            <native:text>{{ __('health.leaning_on_it') }}</native:text>

            @forelse ($this->aboutTheService()->leaning as $name)
                <x-operator::note>{{ $name }}</x-operator::note>
            @empty
                {{-- Unreachable while the branch above guards it, and
                     written anyway: `F6` wants the empty case to be the
                     same edit as the loop, so that removing the guard
                     cannot silently turn *nothing depends on it* into a
                     blank space. --}}
                <x-operator::note>{{ __('health.nothing_leans_on_it') }}</x-operator::note>
            @endforelse
        @else
            <native:text>{{ __('health.nothing_leans_on_it') }}</native:text>
        @endif
    @endif

    <x-operator::action label="{{ __('health.go_ahead') }}" tap="agree()" />
    <x-operator::action label="{{ __('health.never_mind') }}" tap="neverMind()" />
@else
    {{-- What it all amounts to, as the stack judged it — said before the
         rows, so an operator who opened this because a film would not play
         reads the answer before the list. --}}
    <x-operator::emphasis>{{ __($this->answer()->overall) }}</x-operator::emphasis>

    @if ($this->answer()->isSettling)
        {{-- N1-R27: something here becomes something else on its own, and
             this says how often the screen looks. A screen that refreshes
             silently is one an operator cannot reason about. --}}
        <x-operator::note>{{ __($this->cadence()->saidOnTheScreen()) }}</x-operator::note>
    @endif

    @forelse ($this->answer()->services as $service)
        <x-operator::entry>
            <x-operator::emphasis>{{ $service->name }}</x-operator::emphasis>
            <native:text>{{ __($service->runsSaid) }}</native:text>
            <x-operator::note>
                {{ __('health.in_form', ['form' => $service->form]) }}
            </x-operator::note>
            <x-operator::note>{{ __($service->mattersSaid) }}</x-operator::note>

            @if ($service->exited !== '')
                {{-- What it ended with. A service that is running has no
                     code at all, so this line appears only where there is
                     one — an empty field and a zero are different facts. --}}
                <x-operator::note>
                    {{ __('health.it_exited', ['code' => $service->exited]) }}
                </x-operator::note>
            @endif

            @if ($service->isOurs)
                {{-- N2-R7: the three verbs, offered per service. Not drawn
                     for something this stack does not run: a verb about a
                     host-managed service would be refused by the machine,
                     and offering it teaches an operator that the buttons
                     here are a guess. --}}
                <x-operator::action label="{{ __('health.do.start') }}" tap="wouldYouLike('start', '{{ $service->id->named() }}')" />
                <x-operator::action label="{{ __('health.do.stop') }}" tap="wouldYouLike('stop', '{{ $service->id->named() }}')" />
                <x-operator::action label="{{ __('health.do.restart') }}" tap="wouldYouLike('restart', '{{ $service->id->named() }}')" />
            @else
                <x-operator::note>{{ __('health.host_runs_it') }}</x-operator::note>
            @endif

            <x-operator::action label="{{ __('health.read_its_logs') }}" :goes="$this->goes()->logsOf($service->id)" />
        </x-operator::entry>
    @empty
        {{-- Not the same screen as a stack that could not be asked. Nothing
             running is the state the operator came here to change, and
             saying so is what tells it apart from the obstacle branch. --}}
        <x-operator::emphasis>{{ __('health.nothing_is_running') }}</x-operator::emphasis>
    @endforelse

    {{-- N2-R7's other granularity. The forms come from the stack's own list
         rather than from the rows, so the form an operator opened this
         screen to start — the one with nothing running in it — is here. --}}
    <x-operator::emphasis>{{ __('health.by_form') }}</x-operator::emphasis>

    @forelse ($this->answer()->forms as $form)
        <x-operator::entry>
            <native:text>{{ $form }}</native:text>
            <x-operator::action label="{{ __('health.do.start') }}" tap="wouldYouLike('start', '{{ $form }}')" />
            <x-operator::action label="{{ __('health.do.stop') }}" tap="wouldYouLike('stop', '{{ $form }}')" />
            <x-operator::action label="{{ __('health.do.restart') }}" tap="wouldYouLike('restart', '{{ $form }}')" />
        </x-operator::entry>
    @empty
        {{-- A stack with no forms at all is a machine nothing has been set
             up on, which is not the same as one whose forms are all
             stopped — and it is not something *ask again* fixes. --}}
        <native:text>{{ __('health.no_forms_at_all') }}</native:text>
    @endforelse

    <x-operator::action label="{{ __('health.ask_again') }}" tap="again()" />
@endif
</x-operator::screen>
