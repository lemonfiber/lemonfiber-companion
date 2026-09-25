<x-operator::screen-opens :title="$this->stack()->name()->shown()" />

@if ($this->answer()->went->cameBack())
<x-operator::content>
@if (! $this->answer()->namesACopy)
    {{-- Opened on no copy at all, so there is nothing to rehearse and
         nothing to agree to. The copies are where one is chosen. --}}
    <x-operator::emphasis>{{ __('stacks.put_back.names_no_copy') }}</x-operator::emphasis>
    <x-operator::action label="{{ __('stacks.copy.see_the_copies') }}" :goes="$this->goes()->ofItself()->keeps()" />
@elseif ($this->wasAgreedTo())
    {{-- What the stack did, drawn from its report and never from the
         listing above it, so a rehearsal cannot read as a restore. --}}
    @if (! $this->done()->went->cameBack())
        <x-operator::what-stopped-the-reading
            :went="$this->done()->went"
            :sign-in-goes-to="$this->goes()->signIn()"
        />
    @elseif ($this->done()->isWorking)
        <x-operator::emphasis>{{ __('stacks.put_back.putting_back', ['copy' => $this->copyNamed()]) }}</x-operator::emphasis>

        {{-- The stack says how far a restore got once it has finished, and
             not while it runs. --}}
        <native:text>{{ __('stacks.put_back.no_progress_while_running') }}</native:text>
        <x-operator::note>
            {{ __($this->cadence()->saidOnTheScreen(), ['count' => $this->cadence()->seconds()]) }}
        </x-operator::note>
    @elseif ($this->done()->hasEnded)
        {{-- Not a failure: it may well have been put back, and the machine's
             health is where to look. --}}
        <x-operator::emphasis>{{ __('stacks.put_back.no_outcome') }}</x-operator::emphasis>
        <native:text>{{ __('stacks.put_back.no_outcome_action') }}</native:text>
    @else
        <x-operator::emphasis>{{ __('stacks.put_back.put_back', ['scope' => __($this->done()->scope->said, $this->done()->scope->with), 'version' => $this->done()->takenBy]) }}</x-operator::emphasis>

        @forelse ($this->done()->scope->trees as $tree)
            <x-operator::note>{{ $tree }}</x-operator::note>
        @empty
            {{-- Only a copy of an existing setup names the trees it read. --}}
        @endforelse

        @if ($this->done()->relocation !== null)
            {{-- Said wherever it happened: a restore that moved a library
                 without saying so is one its operator believes failed. --}}
            <native:text>{{ __('stacks.put_back.moved', ['was' => $this->done()->relocation->was, 'now' => $this->done()->relocation->now]) }}</native:text>
        @else
            <native:text>{{ __('stacks.put_back.where_it_was') }}</native:text>
        @endif

        <x-operator::action label="{{ __('stacks.copy.see_the_copies') }}" :goes="$this->goes()->ofItself()->keeps()" />
    @endif

    <x-operator::quiet-action label="{{ __('health.ask_again') }}" tap="again()" />
@else
    {{-- A rehearsal, and said to be one before anything else: nothing below
         has happened, and nothing is worded as though it had. --}}
    <x-operator::heading>{{ __('stacks.put_back.a_rehearsal') }}</x-operator::heading>

    <x-operator::emphasis>{{ __('stacks.put_back.would_put_back', ['scope' => __($this->answer()->scope->said, $this->answer()->scope->with)]) }}</x-operator::emphasis>

    @forelse ($this->answer()->scope->trees as $tree)
        <x-operator::note>{{ $tree }}</x-operator::note>
    @empty
        {{-- Only a copy of an existing setup names the trees it read. --}}
    @endforelse

    <native:text>{{ __('stacks.put_back.taken_by', ['version' => $this->answer()->takenBy, 'at' => $this->answer()->takenAt]) }}</native:text>

    @if ($this->answer()->isOlder)
        <x-operator::emphasis>{{ __('stacks.put_back.older') }}</x-operator::emphasis>
    @endif

    {{-- Where the data would land, before the yes rather than after it. --}}
    @if ($this->answer()->relocation !== null)
        <x-operator::emphasis>{{ __('stacks.put_back.would_move', ['was' => $this->answer()->relocation->was, 'now' => $this->answer()->relocation->now]) }}</x-operator::emphasis>
    @else
        <native:text>{{ __('stacks.put_back.would_go_where_it_was') }}</native:text>
    @endif

    <x-operator::emphasis>{{ __('stacks.put_back.would_overwrite') }}</x-operator::emphasis>

    @forelse ($this->answer()->contents as $held)
        <x-operator::note>{{ $held }}</x-operator::note>
    @empty
        <x-operator::note>{{ __('stacks.put_back.holds_nothing') }}</x-operator::note>
    @endforelse

    <native:text>{{ __('stacks.put_back.only_while_stopped') }}</native:text>

    <x-operator::action label="{{ __('stacks.put_back.put_it_back') }}" tap="agree()" />
    <x-operator::quiet-action label="{{ __('health.ask_again') }}" tap="again()" />
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

<x-operator::screen-closes :goes="$this->goes()" here="health" />
