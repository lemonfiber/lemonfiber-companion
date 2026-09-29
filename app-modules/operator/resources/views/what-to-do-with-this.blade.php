<x-operator::screen-opens :title="$this->stack()->name()->shown()" />

@if ($this->answer()->went->cameBack())
<x-operator::content>
@if ($this->asking() !== null)
    {{-- What this will take away, stated before the yes and not
         after it. A confirmation an operator can tap past without reading
         is the same as no confirmation. --}}
    <x-design::title>{{ __('health.about_to', ['what' => $this->asking()->named()]) }}</x-design::title>
    <x-design::body>{{ __($this->asking()->doing()->saidOnTheScreen()) }}</x-design::body>

    {{-- How long for, as the stack reported it. Said here because
         this is the moment it is any use: *a second* and *three minutes*
         are different decisions, and the decision is made before the verb
         runs. There is no fallback sentence: a length this app invented
         would be a guess at something the stack knows, wrong in exactly the
         cases somebody most needs it, and wrong silently. --}}
    <x-design::note>{{ __($this->whatItTakesAway()->said, ['seconds' => $this->whatItTakesAway()->seconds]) }}</x-design::note>

    @if ($this->thing()->isAForm)
        {{-- A whole form is every service in it, which is more than the
             operator picked and has to be said as such. --}}
        <x-design::notice>
            <x-design::body>{{ __('health.about_to_form') }}</x-design::body>
        </x-design::notice>
    @else
        @if ($this->aRestartWouldNotHelp())
            {{-- The honest statement for a restart of a service that is
                 already being started over and over: another restart adds a
                 start to a queue of starts. --}}
            <x-design::notice>
                <x-design::strong>{{ __('health.would_not_help') }}</x-design::strong>
            </x-design::notice>
        @endif

        @if (count($this->thing()->service?->leaning ?? []) > 0)
            {{-- What will not work without it. The part an
                 operator cannot work out from the row they tapped. --}}
            <x-design::section :label="__('health.leaning_on_it')">
                @forelse ($this->thing()->service->leaning as $name)
                    <x-design::row :headline="$name" />
                @empty
                {{-- Unreachable while the branch above guards it, and
                     written anyway: `F6` wants the empty case to be the
                     same edit as the loop, so that removing the guard
                     cannot silently turn *nothing depends on it* into a
                     blank space. --}}
                    <x-design::row :headline="__('health.nothing_leans_on_it')" />
                @endforelse
            </x-design::section>
        @else
            <x-design::body>{{ __('health.nothing_leans_on_it') }}</x-design::body>
        @endif
    @endif

    <x-design::action label="{{ __('health.go_ahead') }}" tap="agree()" />
    <x-design::action label="{{ __('health.never_mind') }}" tap="neverMind()" tone="tonal" />
@elseif (! $this->thing()->isRun())
    {{-- A route naming something the machine is not running. Real rather
         than defensive: a list tapped a moment before the stack changed,
         or a screen restored after a service was taken out of the form.
         Said plainly, because a frame with nothing on it reads as a screen
         that failed to draw rather than as an answer. --}}
    <x-design::notice tone="unknown">
        <x-design::strong>{{ __('health.nothing_of_that_name', ['name' => $this->thing()->named]) }}</x-design::strong>
    </x-design::notice>
    <x-design::action label="{{ __('health.back_to_what_runs') }}" :goes="$this->goes()->services()" />
@else
    {{-- Which thing, and for a service how it stands, as the glyph and
         words its row on the list carries. --}}
    @if ($this->thing()->isAForm)
        <x-design::title>{{ $this->thing()->named }}</x-design::title>
    @else
        <x-design::standing
            :said="$this->thing()->service->name"
            :tone="$this->thing()->service->tone"
            :note="__($this->thing()->service->runsSaid)"
        />
    @endif

    @if ($this->whatItCameTo()->wasAsked)
        {{-- What the last verb sent from here came to, from the stack's own
             report of it. The listing below says where things stand; this
             says what the verb did, which the listing cannot. --}}
        <x-design::card>
            <x-design::heading>{{ __('health.came_to.heading') }}</x-design::heading>

            @if (! $this->whatItCameTo()->went->cameBack())
                <x-operator::what-stood-in-the-way
                    :went="$this->whatItCameTo()->went"
                    :sign-in-goes-to="$this->goes()->signIn()"
                />
            @elseif ($this->whatItCameTo()->isWorking)
                @if ($this->waitsOn !== '')
                    {{-- The stack's own line for what the start is waiting for,
                         in place of this screen's sentence and with no progress
                         figure of its own beside it. --}}
                    <x-design::body>{{ $this->waitsOn }}</x-design::body>
                @else
                    <x-design::body>{{ __('health.came_to.running') }}</x-design::body>
                @endif
            @elseif ($this->whatItCameTo()->hasEnded)
                {{-- Not a failure: it may well have worked, and the listing is
                     where to look. --}}
                <x-design::body>{{ __('health.came_to.no_outcome') }}</x-design::body>
            @else
                @if ($this->whatItCameTo()->wasRehearsed)
                    {{-- A rehearsal changed nothing, and every sentence below is
                         worded as what would happen. --}}
                    <x-design::strong>{{ __('health.came_to.a_rehearsal') }}</x-design::strong>
                @endif

                <x-design::body>{{ __($this->whatItCameTo()->cameToSaid, ['why' => $this->whatItCameTo()->because]) }}</x-design::body>
                <x-design::note>{{ __($this->whatItCameTo()->amountsToSaid) }}</x-design::note>

                @if ($this->whatItCameTo()->namesWhatDidNotComeBack)
                    {{-- Every service short of running, by name and with where it
                         stood: a restart that brought back four of five is never
                         a completed start, and the fifth is the one to go to. --}}
                    @forelse ($this->whatItCameTo()->notBack as $service)
                        <x-design::note>{{ __('health.came_to.not_back', ['name' => $service->name, 'runs' => __($service->runsSaid)]) }}</x-design::note>
                    @empty
                        <x-design::note>{{ __('health.came_to.none_named') }}</x-design::note>
                    @endforelse
                @endif

                @forelse ($this->whatItCameTo()->leftOut as $left)
                    {{-- Left out on purpose, with what it needed, so a service
                         filtered by the configuration never reads as one that
                         failed. --}}
                    <x-design::note>{{ __($this->whatItCameTo()->leftOutSaid, ['name' => $left->name, 'needs' => __($left->needsSaid)]) }}</x-design::note>
                @empty
                    {{-- Nothing left out is nothing to say about the verb. --}}
                @endforelse

                @forelse ($this->whatItCameTo()->portsHeld as $held)
                    {{-- The stack goes ahead where a port is shared, and names
                         both sides so a service that cannot bind can be traced
                         to what holds its port. --}}
                    <x-design::note>{{ __('health.came_to.port_held', ['port' => $held->port, 'wanted_by' => $held->wantedBy, 'held_by' => $held->heldBy]) }}</x-design::note>
                @empty
                    {{-- No port it wanted was held, which is the ordinary case. --}}
                @endforelse
            @endif
        </x-design::card>
    @endif

    @if ($this->thing()->isAForm)
        {{-- What a form is, said once, because the verbs below reach every
             service in it and that is more than the word suggests. --}}
        <x-design::body>{{ __('health.a_whole_form') }}</x-design::body>

        {{-- What starting it would come to, before the verbs that start it,
             and labelled as a rehearsal: nothing below has started. A service
             left out says what it would need, so a service filtered on
             purpose never reads as one that failed. --}}
        <x-design::card>
            <x-design::heading>{{ __('health.rehearsal.heading') }}</x-design::heading>
            <x-design::note>{{ __('health.rehearsal.nothing_started') }}</x-design::note>

            @if (! $this->rehearsal()->went->cameBack())
                <x-operator::what-stood-in-the-way
                    :went="$this->rehearsal()->went"
                    :sign-in-goes-to="$this->goes()->signIn()"
                />
            @else
                @forelse ($this->rehearsal()->wouldStart as $service)
                    <x-design::body>{{ __('health.rehearsal.would_start', ['name' => $service]) }}</x-design::body>
                @empty
                    <x-design::body>{{ __('health.rehearsal.would_start_nothing') }}</x-design::body>
                @endforelse

                @forelse ($this->rehearsal()->leftOut as $left)
                    <x-design::note>{{ __('health.rehearsal.left_out', ['name' => $left->name, 'needs' => __($left->needsSaid)]) }}</x-design::note>
                @empty
                    <x-design::note>{{ __('health.rehearsal.nothing_left_out') }}</x-design::note>
                @endforelse

                {{-- The stack's estimate, said as one, and the services it could
                     not estimate, so a short sum reads as short. --}}
                <x-design::note>{{ __('health.rehearsal.estimate', ['mib' => $this->rehearsal()->estimatedMib]) }}</x-design::note>

                @if ($this->rehearsal()->unestimated !== [])
                    <x-design::note>{{ __('health.rehearsal.unestimated', ['services' => implode(', ', $this->rehearsal()->unestimated)]) }}</x-design::note>
                @endif
            @endif
        </x-design::card>
    @else
        <x-design::card>
            <x-design::body>{{ __($this->thing()->service->mattersSaid) }}</x-design::body>

            @if ($this->thing()->service->exited !== '')
                {{-- What it ended with. A service that is running has no code at
                     all, so this line appears only where there is one — an empty
                     field and a zero are different facts. --}}
                <x-design::note>{{ __('health.it_exited', ['code' => $this->thing()->service->exited]) }}</x-design::note>
            @endif

            @forelse ($this->thing()->service->leaning as $name)
                <x-design::note>{{ __('health.leaned_on_by', ['name' => $name]) }}</x-design::note>
            @empty
                <x-design::note>{{ __('health.nothing_leans_on_it') }}</x-design::note>
            @endforelse
        </x-design::card>
    @endif

    @forelse ($this->thing()->verbs as $verb)
        {{-- The verbs, and only the ones this state can take. There is
             one subject on this frame, so the label is the whole name a
             reader needs — which is the difference between a verb here and
             the same verb drawn once per row on the listing. --}}
        <x-design::action label="{{ __($verb->saidOnTheScreen()) }}" tap="wouldYouLike('{{ $verb->value }}')" />
    @empty
        {{-- Said rather than left blank: a thing this stack runs and offers
             nothing for reads as a frame whose buttons failed to draw. --}}
        @if ($this->thing()->service?->isOurs === false)
            {{-- The reason, where there is one worth giving. A verb about
                 something the host runs would be refused by the machine, and
                 *nothing to do with it* alone reads as the app having run out
                 of ideas rather than as the machine's own arrangement. --}}
            <x-design::note>{{ __('health.host_runs_it') }}</x-design::note>
        @else
            <x-design::note>{{ __('health.nothing_to_do_with_it') }}</x-design::note>
        @endif
    @endforelse

    @unless ($this->thing()->isAForm)
        <x-design::link
            label="{{ __('health.read_its_logs') }}"
            :goes="$this->goes()->logsOf($this->thing()->service->id)"
        />
    @endunless

    <x-design::action label="{{ __('health.ask_again') }}" tap="again()" tone="tonal" />
@endif
</x-operator::content>
@else
    <x-operator::what-stopped-the-reading
        :went="$this->answer()->went"
        :sign-in-goes-to="$this->goes()->signIn()"
    />
@endif

<x-operator::screen-closes :goes="$this->goes()" here="services" />
