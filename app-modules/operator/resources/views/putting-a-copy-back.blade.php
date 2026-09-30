<x-operator::screen-opens :title="$this->stack()->name()->shown()" />

@if ($this->answer()->went->cameBack())
<x-operator::content>
@if (! $this->answer()->namesACopy)
    {{-- Opened on no copy at all, so there is nothing to rehearse and
         nothing to agree to. The copies are where one is chosen. --}}
    <x-design::notice tone="unknown">
        <x-design::strong>{{ __('stacks.put_back.names_no_copy') }}</x-design::strong>
    </x-design::notice>
    <x-design::action label="{{ __('stacks.copy.see_the_copies') }}" :goes="$this->goes()->ofItself()->keeps()" />
@elseif ($this->answer()->refused !== null)
    {{-- The stack's answer, in its words, and not a fault: nothing is listed,
         so nothing can be agreed to, and asking the same again is answered
         the same way. The copies are the road offered. --}}
    <x-design::heading>{{ __('stacks.put_back.would_not_list') }}</x-design::heading>
    <x-operator::refused-in-its-words :refused="$this->answer()->refused" />
    <x-design::body>{{ __('stacks.put_back.same_answer') }}</x-design::body>
    <x-design::action label="{{ __('stacks.copy.see_the_copies') }}" :goes="$this->goes()->ofItself()->keeps()" />
@elseif ($this->wasAgreedTo())
    {{-- What the stack did, drawn from its report and never from the
         listing above it, so a rehearsal cannot read as a restore. --}}
    @if (! $this->done()->went->cameBack())
        <x-operator::what-stood-in-the-way
            :went="$this->done()->went"
            :sign-in-goes-to="$this->goes()->signIn()"
        />
    @elseif ($this->done()->isWorking)
        <x-design::standing
            :said="__('stacks.put_back.putting_back', ['copy' => $this->copyNamed()])"
            tone="working"
        />

        {{-- The stack says how far a restore got once it has finished, and
             not while it runs. --}}
        <x-design::body>{{ __('stacks.put_back.no_progress_while_running') }}</x-design::body>
    @elseif ($this->done()->refused !== null)
        {{-- The stack's answer about the yes, in its words. Asking after the
             same work is answered the same way; reading the listing again is
             a new question, and the one road offered. --}}
        <x-design::heading>{{ __('stacks.put_back.refused') }}</x-design::heading>
        <x-operator::refused-in-its-words :refused="$this->done()->refused" />
        <x-design::body>{{ __('stacks.put_back.same_answer') }}</x-design::body>
        <x-design::action label="{{ __('stacks.put_back.look_again') }}" tap="lookAgain()" />
    @elseif ($this->done()->hasEnded)
        {{-- Not a failure: it may well have been put back, and the machine's
             health is where to look. --}}
        <x-design::notice tone="unknown">
            <x-design::strong>{{ __('stacks.put_back.no_outcome') }}</x-design::strong>
            <x-design::body>{{ __('stacks.put_back.no_outcome_action') }}</x-design::body>
        </x-design::notice>
    @else
        <x-design::card>
            <x-design::strong>{{ __('stacks.put_back.put_back', ['scope' => __($this->done()->scope->said, $this->done()->scope->with), 'version' => $this->done()->takenBy]) }}</x-design::strong>

            @forelse ($this->done()->scope->trees as $tree)
                <x-design::verbatim>{{ $tree }}</x-design::verbatim>
            @empty
                {{-- Only a copy of an existing setup names the trees it read. --}}
            @endforelse

            @if ($this->done()->relocation !== null)
                {{-- Said wherever it happened: a restore that moved a library
                     without saying so is one its operator believes failed. --}}
                <x-design::body>{{ __('stacks.put_back.moved', ['was' => $this->done()->relocation->was, 'now' => $this->done()->relocation->now]) }}</x-design::body>
            @else
                <x-design::body>{{ __('stacks.put_back.where_it_was') }}</x-design::body>
            @endif
        </x-design::card>

        <x-design::action label="{{ __('stacks.copy.see_the_copies') }}" :goes="$this->goes()->ofItself()->keeps()" />
    @endif

    @if ($this->done()->refused === null)
        <x-design::action label="{{ __('health.ask_again') }}" tap="again()" tone="tonal" />
    @endif
@else
    {{-- A rehearsal, and said to be one before anything else: nothing below
         has happened, and nothing is worded as though it had. --}}
    <x-design::heading>{{ __('stacks.put_back.a_rehearsal') }}</x-design::heading>

    <x-design::card>
        <x-design::strong>{{ __('stacks.put_back.would_put_back', ['scope' => __($this->answer()->scope->said, $this->answer()->scope->with)]) }}</x-design::strong>

        @forelse ($this->answer()->scope->trees as $tree)
            <x-design::verbatim>{{ $tree }}</x-design::verbatim>
        @empty
            {{-- Only a copy of an existing setup names the trees it read. --}}
        @endforelse

        <x-design::note>{{ __('stacks.put_back.taken_by', ['version' => $this->answer()->takenBy, 'at' => $this->answer()->takenAt]) }}</x-design::note>

        {{-- Where the data would land, before the yes rather than after it. --}}
        @if ($this->answer()->relocation !== null)
            <x-design::strong>{{ __('stacks.put_back.would_move', ['was' => $this->answer()->relocation->was, 'now' => $this->answer()->relocation->now]) }}</x-design::strong>
        @else
            <x-design::body>{{ __('stacks.put_back.would_go_where_it_was') }}</x-design::body>
        @endif
    </x-design::card>

    @if ($this->answer()->isOlder)
        <x-design::notice>
            <x-design::body>{{ __('stacks.put_back.older') }}</x-design::body>
        </x-design::notice>
    @endif

    {{-- What it would overwrite, named, between the listing and the yes. --}}
    <x-design::notice>
        <x-design::strong>{{ __('stacks.put_back.would_overwrite') }}</x-design::strong>

        @forelse ($this->answer()->contents as $held)
            <x-design::body>{{ $held }}</x-design::body>
        @empty
            <x-design::body>{{ __('stacks.put_back.holds_nothing') }}</x-design::body>
        @endforelse
    </x-design::notice>

    <x-design::note>{{ __('stacks.put_back.only_while_stopped') }}</x-design::note>

    <x-design::action label="{{ __('stacks.put_back.put_it_back') }}" tap="agree()" />
    <x-design::action label="{{ __('health.ask_again') }}" tap="again()" tone="tonal" />
@endif
</x-operator::content>
@else
    {{-- The stack would not list this copy, so there is nothing to agree
         to and nothing is offered. --}}
    <x-operator::what-stopped-the-reading
        :went="$this->answer()->went"
        :sign-in-goes-to="$this->goes()->signIn()"
    />
@endif

<x-operator::screen-closes :goes="$this->goes()" :here="$this->itsTab()" />
