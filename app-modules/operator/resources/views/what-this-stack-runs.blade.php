<x-operator::screen-opens :title="$this->stack()->name()->shown()" />

@if ($this->answer()->went->cameBack())
<native:column class="w-full gap-4 px-6 py-4">
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
                {{-- N2-R7's verbs, and only the ones this state can take.

                     All three on every row put *Start it* on a service that is
                     running and *Stop it* on one that has crashed, and both
                     would be refused by the machine. The argument is the one
                     the arm below already makes about a host-managed service:
                     offering a verb the machine would refuse teaches an
                     operator that the buttons here are a guess. A stack with
                     three services drew twelve controls, nine of which did
                     something and three of which did not, and nothing on the
                     row said which was which.

                     {@see \Modules\Kernel\Api\HowAServiceRuns::whatMayBeDoneToIt()}
                     is what decides, because the state is what knows — a
                     screen working it out from the word on the row would be a
                     second opinion, and two screens could reach different
                     ones. --}}
                @forelse ($service->verbs as $verb)
                    <x-operator::action label="{{ __($verb->saidOnTheScreen()) }}" tap="wouldYouLike('{{ $verb->value }}', '{{ $service->id->named() }}')" />
                @empty
                    {{-- No state has an empty answer today, and one added
                         tomorrow might. Said rather than left blank, because a
                         row that is this stack's and offers nothing reads as a
                         row whose buttons failed to draw. --}}
                    <x-operator::note>{{ __('health.nothing_to_do_with_it') }}</x-operator::note>
                @endforelse
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
            {{-- All three here, and that is not an oversight. A form is a group
                 of services with no single state of its own: some of what is in
                 it may be up and some down, so every one of the three is a real
                 thing to want and none of them is the guess a row's would
                 be. --}}
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
</native:column>
@else
    <x-operator::what-stopped-the-reading
        :went="$this->answer()->went"
        :sign-in-goes-to="$this->goes()->signIn()"
    />
@endif

<x-operator::screen-closes :goes="$this->goes()" here="services" />
