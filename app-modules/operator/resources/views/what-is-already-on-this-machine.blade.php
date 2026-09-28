<x-operator::screen-opens :title="$this->stack()->name()->shown()" />

@if ($this->answer()->went->cameBack())
<x-operator::content>
    <x-operator::heading>{{ __('stacks.already_here.found') }}</x-operator::heading>

    {{-- Every project and service first, before any mode: a mode chosen
         before this is read is chosen blind. --}}
    @if ($this->answer()->looked)
        @forelse ($this->answer()->projects as $project)
            <x-operator::emphasis>{{ __('stacks.already_here.project', ['project' => $project->project]) }}</x-operator::emphasis>
            @forelse ($project->services as $service)
                <x-operator::entry>
                    <x-operator::emphasis>{{ $service->service }}</x-operator::emphasis>
                    <native:text>{{ __($service->runningSaid) }}</native:text>
                    <native:text>{{ __($service->adoptableSaid) }}</native:text>
                    @if ($service->ports !== '')
                        <x-operator::note>{{ __('stacks.already_here.ports', ['ports' => $service->ports]) }}</x-operator::note>
                    @else
                        <x-operator::note>{{ __('stacks.already_here.no_ports') }}</x-operator::note>
                    @endif
                </x-operator::entry>
            @empty
                <x-operator::note>{{ __('stacks.already_here.no_services') }}</x-operator::note>
            @endforelse
        @empty
            <x-operator::note>{{ __('stacks.already_here.nothing_found') }}</x-operator::note>
        @endforelse

        {{-- Each port in the way names what already holds it. --}}
        <x-operator::heading>{{ __('stacks.already_here.conflicts') }}</x-operator::heading>
        @forelse ($this->answer()->conflicts as $line)
            <native:text>{{ __($line->said, $line->with) }}</native:text>
        @empty
            <x-operator::note>{{ __('stacks.already_here.no_conflicts') }}</x-operator::note>
        @endforelse

        {{-- Where each service would be reached instead, drawn on every
             reading for as long as the stack reports it. --}}
        <x-operator::heading>{{ __('stacks.already_here.beside') }}</x-operator::heading>
        @forelse ($this->answer()->beside as $line)
            <native:text>{{ __($line->said, $line->with) }}</native:text>
        @empty
            <x-operator::note>{{ __('stacks.already_here.none_moved') }}</x-operator::note>
        @endforelse

        <x-operator::heading>{{ __('stacks.already_here.cannot_take') }}</x-operator::heading>
        @forelse ($this->answer()->unsupported as $line)
            <native:text>{{ __($line->said, $line->with) }}</native:text>
        @empty
            <x-operator::note>{{ __('stacks.already_here.nothing_unsupported') }}</x-operator::note>
        @endforelse
    @else
        {{-- A survey that could not look is never drawn as a machine with
             nothing on it, and says nothing about what is in the way. --}}
        <x-operator::emphasis>{{ __('stacks.already_here.could_not_look') }}</x-operator::emphasis>
    @endif

    {{-- The remedy is words and nothing more: no act the stack offers carries
         it out, and it is the operator's to take on their own disks. --}}
    @if ($this->answer()->linking->because !== '')
        <x-operator::heading>{{ __('stacks.already_here.cannot_link') }}</x-operator::heading>
        <native:text>{{ $this->answer()->linking->because }}</native:text>
        <native:text>{{ $this->answer()->linking->cost }}</native:text>
        @if ($this->answer()->linking->filesystems !== '')
            <x-operator::note>{{ __('stacks.already_here.filesystems', ['filesystems' => $this->answer()->linking->filesystems]) }}</x-operator::note>
        @endif
        <native:text>{{ $this->answer()->linking->remedy }}</native:text>
        <x-operator::note>{{ __('stacks.already_here.remedy_is_yours') }}</x-operator::note>
    @endif

    {{-- The modes last, in the stack's order, each with whether it disturbs
         what is running. Chosen already only where the survey says so. Each
         the app can carry out is asked about first, and nothing is moved by
         asking. --}}
    <x-operator::heading>{{ __('stacks.already_here.modes') }}</x-operator::heading>
    @forelse ($this->answer()->modes as $mode)
        <x-operator::entry>
            <x-operator::emphasis>{{ $mode->mode }}</x-operator::emphasis>
            <native:text>{{ $mode->what }}</native:text>
            <x-operator::note>{{ __($mode->disturbsSaid) }}</x-operator::note>
            @if ($mode->preselected)
                <x-operator::note>{{ __('stacks.already_here.preselected') }}</x-operator::note>
            @endif
            @if ($mode->askSaid !== '')
                <x-operator::quiet-action label="{{ __($mode->askSaid) }}" tap="wouldMoveIn('{{ $mode->mode }}')" />
            @endif
        </x-operator::entry>
    @empty
        <x-operator::note>{{ __('stacks.already_here.no_modes') }}</x-operator::note>
    @endforelse

    @if ($this->howTheMoveIsGoing()->went->cameBack())
        @if ($this->howTheMoveIsGoing()->mode !== '')
            <x-operator::heading>{{ __('stacks.moving_in.about', ['mode' => $this->howTheMoveIsGoing()->mode]) }}</x-operator::heading>
        @endif
        @if ($this->howTheMoveIsGoing()->isWorking)
            <native:text>{{ __('stacks.moving_in.working') }}</native:text>
            <x-operator::note>
                {{ __($this->cadence()->saidOnTheScreen(), ['count' => $this->cadence()->seconds()]) }}
            </x-operator::note>
        @elseif ($this->howTheMoveIsGoing()->hasEnded)
            {{-- Not a failure and not a refusal: the stack has no outcome for
                 it any more. --}}
            <native:text>{{ __('stacks.moving_in.no_outcome') }}</native:text>
            <x-operator::quiet-action label="{{ __('stacks.moving_in.leave_it') }}" tap="leaveIt()" />
        @elseif ($this->howTheMoveIsGoing()->refusal !== '')
            {{-- The stack's answer, drawn as the reason it is rather than as
                 something to try again. --}}
            <x-operator::emphasis>{{ __('stacks.moving_in.refused') }}</x-operator::emphasis>
            <native:text>{{ $this->howTheMoveIsGoing()->refusal }}</native:text>
            <x-operator::quiet-action label="{{ __('stacks.moving_in.leave_it') }}" tap="leaveIt()" />
        @elseif ($this->howTheMoveIsGoing()->move !== null)
            {{-- What did not come across leads, with the reason for each,
                 before where the move stands and before what did. --}}
            @if ($this->howTheMoveIsGoing()->move->leftBehindSaid !== '')
                <x-operator::heading>{{ __($this->howTheMoveIsGoing()->move->leftBehindSaid) }}</x-operator::heading>
                @forelse ($this->howTheMoveIsGoing()->move->leftBehind as $line)
                    <x-operator::emphasis>{{ __($line->said, $line->with) }}</x-operator::emphasis>
                @empty
                    <x-operator::note>{{ __('stacks.moving_in.nothing_left_behind') }}</x-operator::note>
                @endforelse
            @endif

            {{-- Where it stands, as the stack gave it; a move turned away
                 carries the stack's reason and nothing to try again. --}}
            <x-operator::emphasis>{{ __($this->howTheMoveIsGoing()->move->stanceSaid) }}</x-operator::emphasis>
            @if ($this->howTheMoveIsGoing()->move->refusal !== '')
                <native:text>{{ $this->howTheMoveIsGoing()->move->refusal }}</native:text>
            @endif
            @forelse ($this->howTheMoveIsGoing()->move->lines as $line)
                <native:text>{{ __($line->said, $line->with) }}</native:text>
            @empty
                <x-operator::note>{{ __('stacks.moving_in.nothing_listed') }}</x-operator::note>
            @endforelse

            @if ($this->howTheMoveIsGoing()->move->agreeSaid !== '')
                {{-- What is copied first, said before the yes rather than
                     after it. --}}
                <x-operator::heading>{{ __('stacks.moving_in.before_you_agree') }}</x-operator::heading>
                @forelse ($this->howTheMoveIsGoing()->move->copyFirst as $line)
                    <native:text>{{ __($line->said, $line->with) }}</native:text>
                @empty
                    <x-operator::note>{{ __('stacks.moving_in.nothing_copied_first') }}</x-operator::note>
                @endforelse
                <x-operator::action label="{{ __($this->howTheMoveIsGoing()->move->agreeSaid) }}" tap="moveIn()" />
            @endif
            <x-operator::quiet-action label="{{ __('stacks.moving_in.leave_it') }}" tap="leaveIt()" />
        @endif
    @else
        {{-- Asking, or asking after it, met something: said where the answer
             would have been, with the way back. --}}
        <x-operator::what-stopped-the-reading
            :went="$this->howTheMoveIsGoing()->went"
            :sign-in-goes-to="$this->goes()->signIn()"
        />
    @endif

    {{-- Looking again reads the survey, and asks after work still being
         followed. It is not a retry of anything the stack refused, and is
         named for what it does. --}}
    <x-operator::action label="{{ __('stacks.already_here.look_again') }}" tap="again()" />
</x-operator::content>
@else
    <x-operator::what-stopped-the-reading
        :went="$this->answer()->went"
        :sign-in-goes-to="$this->goes()->signIn()"
    />
@endif

<x-operator::screen-closes :goes="$this->goes()" here="health" />
