@use('Modules\Stacks\Api\AStacksScreen')
<x-wayfinding::stack-opens :title="$this->stack()->name()->shown()" :stacks="$this->stacksToChooseFrom()" :choosing="$this->choosingAStack" />

@if ($this->answer()->went->cameBack())
<x-operator::content>
    <x-design::title>{{ __('stacks.already_here.found') }}</x-design::title>

    {{-- Every project and service first, before any mode: a mode chosen
         before this is read is chosen blind. A project is a card of its
         services, each with whether it runs, whether it could be taken over,
         and the ports it publishes at its end. --}}
    @if ($this->answer()->looked)
        @forelse ($this->answer()->projects as $project)
            <x-design::section :label="__('stacks.already_here.project', ['project' => $project->project])">
                @forelse ($project->services as $service)
                    <x-design::row
                        :headline="$service->service"
                        :supporting="__($service->runningSaid) . ' · ' . __($service->adoptableSaid)"
                        :trailing="__($service->portsSaid, ['ports' => $service->ports])"
                    />
                @empty
                    <x-design::row :headline="__('stacks.already_here.no_services')" />
                @endforelse
            </x-design::section>
        @empty
            <x-design::body>{{ __('stacks.already_here.nothing_found') }}</x-design::body>
        @endforelse

        {{-- Each port in the way names what already holds it. --}}
        <x-design::section :label="__('stacks.already_here.conflicts')">
            @forelse ($this->answer()->conflicts as $line)
                <x-design::row :headline="__($line->said, $line->with)" />
            @empty
                <x-design::row :headline="__('stacks.already_here.no_conflicts')" />
            @endforelse
        </x-design::section>

        {{-- Where each service would be reached instead, drawn on every
             reading for as long as the stack reports it. --}}
        <x-design::section :label="__('stacks.already_here.beside')">
            @forelse ($this->answer()->beside as $line)
                <x-design::row :headline="__($line->said, $line->with)" />
            @empty
                <x-design::row :headline="__('stacks.already_here.none_moved')" />
            @endforelse
        </x-design::section>

        <x-design::section :label="__('stacks.already_here.cannot_take')">
            @forelse ($this->answer()->unsupported as $line)
                <x-design::row :headline="__($line->said, $line->with)" />
            @empty
                <x-design::row :headline="__('stacks.already_here.nothing_unsupported')" />
            @endforelse
        </x-design::section>
    @else
        {{-- A survey that could not look is never drawn as a machine with
             nothing on it, and says nothing about what is in the way. --}}
        <x-design::notice tone="unknown">
            <x-design::strong>{{ __('stacks.already_here.could_not_look') }}</x-design::strong>
        </x-design::notice>
    @endif

    {{-- The remedy is words and nothing more: no act the stack offers carries
         it out, and it is the operator's to take on their own disks. --}}
    @if ($this->answer()->linking->because !== '')
        <x-design::notice>
            <x-design::strong>{{ __('stacks.already_here.cannot_link') }}</x-design::strong>
            <x-design::body>{{ $this->answer()->linking->because }}</x-design::body>
            <x-design::body>{{ $this->answer()->linking->cost }}</x-design::body>
            @if ($this->answer()->linking->filesystems !== '')
                <x-design::note>{{ __('stacks.already_here.filesystems', ['filesystems' => $this->answer()->linking->filesystems]) }}</x-design::note>
            @endif
            <x-design::body>{{ $this->answer()->linking->remedy }}</x-design::body>
            <x-design::note>{{ __('stacks.already_here.remedy_is_yours') }}</x-design::note>
        </x-design::notice>
    @endif

    {{-- The modes last, in the stack's order, each on its card with whether
         it disturbs what is running. Chosen already only where the survey
         says so. Each the app can carry out is asked about first, and nothing
         is moved by asking. --}}
    <x-design::heading>{{ __('stacks.already_here.modes') }}</x-design::heading>
    @forelse ($this->answer()->modes as $mode)
        <x-design::card>
            <x-design::strong>{{ $mode->mode }}</x-design::strong>
            <x-design::body>{{ $mode->what }}</x-design::body>
            <x-design::note>{{ __($mode->disturbsSaid) }}</x-design::note>
            @if ($mode->preselected)
                <x-design::note>{{ __('stacks.already_here.preselected') }}</x-design::note>
            @endif
            @if ($mode->by !== null)
                <x-operator::offered-action label="{{ __($mode->askSaid) }}" tap="wouldMoveIn('{{ $mode->mode }}')" :offer="$this->offered($mode->by)" tone="tonal" />
            @endif
        </x-design::card>
    @empty
        <x-design::body>{{ __('stacks.already_here.no_modes') }}</x-design::body>
    @endforelse

    @if ($this->howTheMoveIsGoing()->went->cameBack())
        @if ($this->howTheMoveIsGoing()->mode !== '')
            <x-design::heading>{{ __('stacks.moving_in.about', ['mode' => $this->howTheMoveIsGoing()->mode]) }}</x-design::heading>
        @endif
        @if ($this->howTheMoveIsGoing()->isWorking)
            <x-design::standing
                :said="__('stacks.moving_in.working')"
                tone="working"
            />
        @elseif ($this->howTheMoveIsGoing()->hasEnded)
            {{-- Not a failure and not a refusal: the stack has no outcome for
                 it any more. --}}
            <x-design::notice tone="unknown">
                <x-design::strong>{{ __('stacks.moving_in.no_outcome') }}</x-design::strong>
            </x-design::notice>
            <x-design::action label="{{ __('stacks.moving_in.leave_it') }}" tap="leaveIt()" tone="tonal" />
        @elseif ($this->howTheMoveIsGoing()->refusal !== '')
            {{-- The stack's answer, drawn as the reason it is rather than as
                 something to try again. --}}
            <x-design::notice>
                <x-design::strong>{{ __('stacks.moving_in.refused') }}</x-design::strong>
                <x-design::body>{{ $this->howTheMoveIsGoing()->refusal }}</x-design::body>
            </x-design::notice>
            <x-design::action label="{{ __('stacks.moving_in.leave_it') }}" tap="leaveIt()" tone="tonal" />
        @elseif ($this->howTheMoveIsGoing()->move !== null)
            {{-- What did not come across leads, with the reason for each,
                 before where the move stands and before what did. --}}
            @if ($this->howTheMoveIsGoing()->move->leftBehindSaid !== '')
                <x-design::section :label="__($this->howTheMoveIsGoing()->move->leftBehindSaid)">
                    @forelse ($this->howTheMoveIsGoing()->move->leftBehind as $line)
                        <x-design::row :headline="__($line->said, $line->with)" />
                    @empty
                        <x-design::row :headline="__('stacks.moving_in.nothing_left_behind')" />
                    @endforelse
                </x-design::section>
            @endif

            {{-- Where it stands, as the stack gave it, over what it came to; a
                 move turned away carries the stack's reason and nothing to try
                 again. --}}
            <x-design::card>
                <x-design::strong>{{ __($this->howTheMoveIsGoing()->move->stanceSaid) }}</x-design::strong>
                @if ($this->howTheMoveIsGoing()->move->refusal !== '')
                    <x-design::body>{{ $this->howTheMoveIsGoing()->move->refusal }}</x-design::body>
                @endif
            </x-design::card>
            <x-design::section>
                @forelse ($this->howTheMoveIsGoing()->move->lines as $line)
                    <x-design::row :headline="__($line->said, $line->with)" />
                @empty
                    <x-design::row :headline="__('stacks.moving_in.nothing_listed')" />
                @endforelse
            </x-design::section>

            @if ($this->howTheMoveIsGoing()->move->agreeSaid !== '')
                {{-- What is copied first, said before the yes rather than
                     after it. --}}
                <x-design::section :label="__('stacks.moving_in.before_you_agree')">
                    @forelse ($this->howTheMoveIsGoing()->move->copyFirst as $line)
                        <x-design::row :headline="__($line->said, $line->with)" />
                    @empty
                        <x-design::row :headline="__('stacks.moving_in.nothing_copied_first')" />
                    @endforelse
                </x-design::section>
                <x-design::action label="{{ __($this->howTheMoveIsGoing()->move->agreeSaid) }}" tap="moveIn()" />
            @endif
            <x-design::action label="{{ __('stacks.moving_in.leave_it') }}" tap="leaveIt()" tone="tonal" />
        @endif
    @else
        {{-- Asking, or asking after it, met something: said where the answer
             would have been, with the way back. --}}
        <x-operator::what-stood-in-the-way
            :settings-would-not-open="$this->theSettingsWouldNotOpen"
            :went="$this->howTheMoveIsGoing()->went"
            :goes="$this->goes()"
        />
        <x-operator::try-again :went="$this->howTheMoveIsGoing()->went" tap="tryAgain()" />
    @endif

    {{-- Looking again reads the survey, and asks after work still being
         followed. It is not a retry of anything the stack refused, and is
         named for what it does. --}}
    <x-design::action label="{{ __('stacks.already_here.look_again') }}" tap="askAgain()" tone="tonal" />
</x-operator::content>
@else
    <x-operator::what-stopped-the-reading
        :settings-would-not-open="$this->theSettingsWouldNotOpen"
        :went="$this->answer()->went"
        :goes="$this->goes()"
    />
@endif

<x-operator::screen-closes :goes="$this->goes()" :here="$this->itsTab()" :marks="$this->marks()" />
