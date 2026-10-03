<x-wayfinding::stack-opens :title="$this->stack()->name()->shown()" :stacks="$this->stacksToChooseFrom()" :choosing="$this->choosingAStack" />

@if ($this->answer()->went->cameBack())
<x-operator::content>
@if ($this->askingAbout() !== null)
    {{-- Asked before it runs, naming what the copy would cover: the whole
         stack and one service are different undertakings, and the yes is to
         one of them. --}}
    <x-design::title>{{ __('stacks.copy.about_to', ['scope' => __($this->askingAbout()->said, $this->askingAbout()->with)]) }}</x-design::title>
    <x-design::body>{{ __('stacks.copy.may_remove') }}</x-design::body>

    <x-design::action label="{{ __('health.go_ahead') }}" tap="agree()" />
    <x-design::action label="{{ __('health.never_mind') }}" tap="neverMind()" tone="tonal" />
@elseif ($this->lastCopy()->wasAsked)
    @if (! $this->lastCopy()->went->cameBack())
        <x-operator::what-stood-in-the-way
            :settings-would-not-open="$this->theSettingsWouldNotOpen"
            :went="$this->lastCopy()->went"
            :sign-in-goes-to="$this->goes()->signIn()"
        />
    @elseif ($this->lastCopy()->isWorking)
        <x-design::standing
            :said="__('stacks.copy.taking', ['scope' => __($this->lastCopy()->scope->said, $this->lastCopy()->scope->with)])"
            tone="working"
        />

        {{-- The stack says how far a copy got once it has finished, and not
             while it runs, so this says that rather than drawing progress
             nobody measured. --}}
        <x-design::body>{{ __('stacks.copy.no_progress_while_running') }}</x-design::body>
    @elseif ($this->lastCopy()->hasEnded)
        {{-- Not a failure: the copy may well exist, and the list of copies
             is where to look. --}}
        <x-design::notice tone="unknown">
            <x-design::strong>{{ __('stacks.copy.no_outcome', ['scope' => __($this->lastCopy()->scope->said, $this->lastCopy()->scope->with)]) }}</x-design::strong>
            <x-design::body>{{ __('stacks.copy.no_outcome_action') }}</x-design::body>
        </x-design::notice>
    @else
        @if ($this->lastCopy()->wasRehearsed)
            {{-- A rehearsal wrote nothing, and every sentence below is
                 worded as what would happen. --}}
            <x-design::heading>{{ __('stacks.copy.a_rehearsal') }}</x-design::heading>
        @endif

        <x-design::card>
            <x-design::strong>{{ __($this->lastCopy()->tookSaid, ['scope' => __($this->lastCopy()->scope->said, $this->lastCopy()->scope->with)]) }}</x-design::strong>

            @forelse ($this->lastCopy()->scope->trees as $tree)
                <x-design::verbatim>{{ $tree }}</x-design::verbatim>
            @empty
                {{-- Only a copy of an existing setup names the trees it read. --}}
            @endforelse

            {{-- Past the budget is the size of what is kept, not a fault, and
                 that is the difference somebody watching a long copy needs. --}}
            <x-design::body>
                {{ __($this->lastCopy()->pace->said, [
                    'moved' => $this->lastCopy()->pace->moved->figure,
                    'moved_unit' => __($this->lastCopy()->pace->moved->unit),
                    'budget' => $this->lastCopy()->pace->budget->figure,
                    'budget_unit' => __($this->lastCopy()->pace->budget->unit),
                ]) }}
            </x-design::body>

            <x-design::note>{{ __($this->lastCopy()->holdsSaid) }}</x-design::note>
        </x-design::card>

        {{-- What it removed is part of what it did, said here rather than
             left to be found as a copy missing from the list. --}}
        <x-design::section :label="trans_choice($this->lastCopy()->prunedSaid, count($this->lastCopy()->pruned))">
            @forelse ($this->lastCopy()->pruned as $name)
                <x-design::row :headline="$name" />
            @empty
                <x-design::row :headline="__('stacks.copy.kept_every_other')" />
            @endforelse
        </x-design::section>

        <x-design::action label="{{ __('stacks.copy.see_the_copies') }}" :goes="$this->goes()->ofItself()->keeps()" />
    @endif

    <x-design::action label="{{ __('health.ask_again') }}" tap="again()" tone="tonal" />
@else
    {{-- The whole stack, or one service on its own: each is a row, and
         tapping one asks before anything is taken. --}}
    <x-design::heading>{{ __('stacks.copy.what_to_copy') }}</x-design::heading>

    <x-design::section>
        <x-design::row :headline="__('stacks.copy.the_whole_stack')" tap="copyTheWholeStack()" />
    </x-design::section>

    <x-design::section :label="__('stacks.copy.or_one_service')">
        @forelse ($this->answer()->services as $service)
            <x-design::row
                :headline="$service->name"
                :answers-to="__('stacks.copy.only_this_service', ['name' => $service->name])"
                tap="copyTheService('{{ $service->id->named() }}')"
            />
        @empty
            <x-design::row :headline="__('stacks.copy.no_services')" />
        @endforelse
    </x-design::section>

    <x-design::action label="{{ __('health.ask_again') }}" tap="again()" tone="tonal" />
@endif
</x-operator::content>
@else
    {{-- The services could not be listed, so nothing is offered to copy:
         a stack that cannot say what it runs is not one to ask for a copy. --}}
    <x-operator::what-stopped-the-reading
        :settings-would-not-open="$this->theSettingsWouldNotOpen"
        :went="$this->answer()->went"
        :sign-in-goes-to="$this->goes()->signIn()"
    />
@endif

<x-operator::screen-closes :goes="$this->goes()" :here="$this->itsTab()" />
