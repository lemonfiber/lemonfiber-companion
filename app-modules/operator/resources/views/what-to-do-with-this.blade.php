<x-operator::screen-opens :title="$this->stack()->name()->shown()" />

@if ($this->answer()->went->cameBack())
<x-operator::content>
@if ($this->asking() !== null)
    {{-- What this will take away, stated before the yes and not
         after it. A confirmation an operator can tap past without reading
         is the same as no confirmation. --}}
    <x-operator::heading>
        {{ __('health.about_to', ['what' => $this->asking()->named()]) }}
    </x-operator::heading>
    <native:text>{{ __($this->asking()->doing()->saidOnTheScreen()) }}</native:text>

    {{-- How long for, as the stack reported it. Said here because
         this is the moment it is any use: *a second* and *three minutes*
         are different decisions, and the decision is made before the verb
         runs. N2-R14 is why there is no fallback sentence — a length this
         app invented would be a guess at something the stack knows, wrong
         in exactly the cases somebody most needs it, and wrong silently. --}}
    <x-operator::note>
        {{ __($this->whatItTakesAway()->said, ['seconds' => $this->whatItTakesAway()->seconds]) }}
    </x-operator::note>

    @if ($this->thing()->isAForm)
        {{-- A whole form is every service in it, which is more than the
             operator picked and has to be said as such. --}}
        <native:text>{{ __('health.about_to_form') }}</native:text>
    @else
        @if ($this->thing()->service?->wouldNotHelp)
            {{-- The honest statement for a service that is already being
                 started over and over: another restart adds a start to a
                 queue of starts. --}}
            <x-operator::emphasis>{{ __('health.would_not_help') }}</x-operator::emphasis>
        @endif

        @if (count($this->thing()->service?->leaning ?? []) > 0)
            {{-- What will not work without it. The part an
                 operator cannot work out from the row they tapped. --}}
            <native:text>{{ __('health.leaning_on_it') }}</native:text>

            @forelse ($this->thing()->service->leaning as $name)
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
    <x-operator::quiet-action label="{{ __('health.never_mind') }}" tap="neverMind()" />
@elseif (! $this->thing()->isRun())
    {{-- A route naming something the machine is not running. Real rather
         than defensive: a list tapped a moment before the stack changed,
         or a screen restored after a service was taken out of the form.
         Said plainly, because a frame with nothing on it reads as a screen
         that failed to draw rather than as an answer. --}}
    <x-operator::emphasis>{{ __('health.nothing_of_that_name', ['name' => $this->thing()->named]) }}</x-operator::emphasis>
    <x-operator::action label="{{ __('health.back_to_what_runs') }}" :goes="$this->goes()->services()" />
@else
    <x-operator::heading>{{ $this->thing()->service?->name ?? $this->thing()->named }}</x-operator::heading>

    @if ($this->thing()->isAForm)
        {{-- What a form is, said once, because the verbs below reach every
             service in it and that is more than the word suggests. --}}
        <native:text>{{ __('health.a_whole_form') }}</native:text>
    @else
        <native:text>{{ __($this->thing()->service->runsSaid) }}</native:text>
        <x-operator::note>{{ __($this->thing()->service->mattersSaid) }}</x-operator::note>

        @if ($this->thing()->service->exited !== '')
            {{-- What it ended with. A service that is running has no code at
                 all, so this line appears only where there is one — an empty
                 field and a zero are different facts. --}}
            <x-operator::note>
                {{ __('health.it_exited', ['code' => $this->thing()->service->exited]) }}
            </x-operator::note>
        @endif

        @forelse ($this->thing()->service->leaning as $name)
            <x-operator::note>{{ __('health.leaned_on_by', ['name' => $name]) }}</x-operator::note>
        @empty
            <x-operator::note>{{ __('health.nothing_leans_on_it') }}</x-operator::note>
        @endforelse
    @endif

    @if ($this->thing()->service?->isSettling)
        {{-- This one becomes something else on its own, and this
             says how often the screen looks. --}}
        <x-operator::note>{{ __($this->cadence()->saidOnTheScreen()) }}</x-operator::note>
    @endif

    @forelse ($this->thing()->verbs as $verb)
        {{-- The verbs, and only the ones this state can take. There is
             one subject on this frame, so the label is the whole name a
             reader needs — which is the difference between a verb here and
             the same verb drawn once per row on the listing. --}}
        <x-operator::action label="{{ __($verb->saidOnTheScreen()) }}" tap="wouldYouLike('{{ $verb->value }}')" />
    @empty
        {{-- Said rather than left blank: a thing this stack runs and offers
             nothing for reads as a frame whose buttons failed to draw. --}}
        @if ($this->thing()->service?->isOurs === false)
            {{-- The reason, where there is one worth giving. A verb about
                 something the host runs would be refused by the machine, and
                 *nothing to do with it* alone reads as the app having run out
                 of ideas rather than as the machine's own arrangement. --}}
            <x-operator::note>{{ __('health.host_runs_it') }}</x-operator::note>
        @else
            <x-operator::note>{{ __('health.nothing_to_do_with_it') }}</x-operator::note>
        @endif
    @endforelse

    @unless ($this->thing()->isAForm)
        <x-operator::quiet-action
            label="{{ __('health.read_its_logs') }}"
            :goes="$this->goes()->logsOf($this->thing()->service->id)"
        />
    @endunless

    <x-operator::quiet-action label="{{ __('health.ask_again') }}" tap="again()" />
@endif
</x-operator::content>
@else
    <x-operator::what-stopped-the-reading
        :went="$this->answer()->went"
        :sign-in-goes-to="$this->goes()->signIn()"
    />
@endif

<x-operator::screen-closes :goes="$this->goes()" here="services" />
