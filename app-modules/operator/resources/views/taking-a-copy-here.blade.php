<x-operator::screen-opens :title="$this->stack()->name()->shown()" />

@if ($this->answer()->went->cameBack())
<x-operator::content>
@if ($this->askingAbout() !== null)
    {{-- Asked before it runs, naming what the copy would cover: the whole
         stack and one service are different undertakings, and the yes is to
         one of them. --}}
    <x-operator::heading>{{ __('stacks.copy.about_to', ['scope' => __($this->askingAbout()->said, $this->askingAbout()->with)]) }}</x-operator::heading>
    <native:text>{{ __('stacks.copy.may_remove') }}</native:text>

    <x-operator::action label="{{ __('health.go_ahead') }}" tap="agree()" />
    <x-operator::action label="{{ __('health.never_mind') }}" tap="neverMind()" />
@elseif ($this->lastCopy()->wasAsked)
    @if (! $this->lastCopy()->went->cameBack())
        <x-operator::what-stopped-the-reading
            :went="$this->lastCopy()->went"
            :sign-in-goes-to="$this->goes()->signIn()"
        />
    @elseif ($this->lastCopy()->isWorking)
        <x-operator::emphasis>{{ __('stacks.copy.taking', ['scope' => __($this->lastCopy()->scope->said, $this->lastCopy()->scope->with)]) }}</x-operator::emphasis>

        {{-- The stack says how far a copy got once it has finished, and not
             while it runs, so this says that rather than drawing progress
             nobody measured. --}}
        <native:text>{{ __('stacks.copy.no_progress_while_running') }}</native:text>
        <x-operator::note>
            {{ __($this->cadence()->saidOnTheScreen(), ['count' => $this->cadence()->seconds()]) }}
        </x-operator::note>
    @elseif ($this->lastCopy()->hasEnded)
        {{-- Not a failure: the copy may well exist, and the list of copies
             is where to look. --}}
        <x-operator::emphasis>{{ __('stacks.copy.no_outcome', ['scope' => __($this->lastCopy()->scope->said, $this->lastCopy()->scope->with)]) }}</x-operator::emphasis>
        <native:text>{{ __('stacks.copy.no_outcome_action') }}</native:text>
    @else
        @if ($this->lastCopy()->wasRehearsed)
            {{-- A rehearsal wrote nothing, and every sentence below is
                 worded as what would happen. --}}
            <x-operator::heading>{{ __('stacks.copy.a_rehearsal') }}</x-operator::heading>
        @endif

        <x-operator::emphasis>{{ __($this->lastCopy()->tookSaid, ['scope' => __($this->lastCopy()->scope->said, $this->lastCopy()->scope->with)]) }}</x-operator::emphasis>

        @forelse ($this->lastCopy()->scope->trees as $tree)
            <x-operator::note>{{ $tree }}</x-operator::note>
        @empty
            {{-- Only a copy of an existing setup names the trees it read. --}}
        @endforelse

        {{-- What it removed is part of what it did, said here rather than
             left to be found as a copy missing from the list. --}}
        <native:text>{{ trans_choice($this->lastCopy()->prunedSaid, count($this->lastCopy()->pruned)) }}</native:text>

        @forelse ($this->lastCopy()->pruned as $name)
            <x-operator::note>{{ $name }}</x-operator::note>
        @empty
            <x-operator::note>{{ __('stacks.copy.kept_every_other') }}</x-operator::note>
        @endforelse

        {{-- Past the budget is the size of what is kept, not a fault, and
             that is the difference somebody watching a long copy needs. --}}
        <native:text>
            {{ __($this->lastCopy()->pace->said, [
                'moved' => $this->lastCopy()->pace->moved->figure,
                'moved_unit' => __($this->lastCopy()->pace->moved->unit),
                'budget' => $this->lastCopy()->pace->budget->figure,
                'budget_unit' => __($this->lastCopy()->pace->budget->unit),
            ]) }}
        </native:text>

        <x-operator::note>{{ __($this->lastCopy()->holdsSaid) }}</x-operator::note>

        <x-operator::action label="{{ __('stacks.copy.see_the_copies') }}" :goes="$this->goes()->ofItself()->keeps()" />
    @endif

    <x-operator::quiet-action label="{{ __('health.ask_again') }}" tap="again()" />
@else
    <x-operator::heading>{{ __('stacks.copy.what_to_copy') }}</x-operator::heading>

    <x-operator::action label="{{ __('stacks.copy.the_whole_stack') }}" tap="copyTheWholeStack()" />

    <x-operator::emphasis>{{ __('stacks.copy.or_one_service') }}</x-operator::emphasis>

    @forelse ($this->answer()->services as $service)
        <x-operator::action
            label="{{ __('stacks.copy.only', ['name' => $service->name]) }}"
            answers-to="{{ __('stacks.copy.only_this_service', ['name' => $service->name]) }}"
            tap="copyTheService('{{ $service->id->named() }}')"
        />
    @empty
        <x-operator::note>{{ __('stacks.copy.no_services') }}</x-operator::note>
    @endforelse

    <x-operator::quiet-action label="{{ __('health.ask_again') }}" tap="again()" />
@endif
</x-operator::content>
@else
    {{-- The services could not be listed, so nothing is offered to copy:
         a stack that cannot say what it runs is not one to ask for a copy. --}}
    <x-operator::what-stopped-the-reading
        :went="$this->answer()->went"
        :sign-in-goes-to="$this->goes()->signIn()"
    />
@endif

<x-operator::screen-closes :goes="$this->goes()" here="health" />
