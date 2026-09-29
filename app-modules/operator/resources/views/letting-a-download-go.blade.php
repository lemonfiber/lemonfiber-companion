<x-operator::screen-opens :title="$this->stack()->name()->shown()" />

@if ($this->answer()->went->cameBack())
<x-operator::content>
@if (! $this->answer()->namesADownload)
    {{-- Opened on no download at all, so there is nothing to ask about and
         nothing to agree to. How full the machine is lists each one. --}}
    <x-operator::emphasis>{{ __('stacks.let_go.names_no_download') }}</x-operator::emphasis>
    <x-operator::action label="{{ __('stacks.let_go.see_the_room') }}" :goes="$this->goes()->ofItself()->room()" />
@elseif ($this->wasAgreedTo())
    {{-- What the stack did, drawn from its report and never from the offer
         above it, so an offer cannot read as something that happened. --}}
    @if (! $this->done()->went->cameBack())
        <x-operator::what-stood-in-the-way
            :went="$this->done()->went"
            :sign-in-goes-to="$this->goes()->signIn()"
        />
    @elseif ($this->done()->isWorking)
        <x-operator::emphasis>{{ __('stacks.let_go.letting_go', ['download' => $this->downloadNamed()]) }}</x-operator::emphasis>
        <x-operator::note>
            {{ __($this->cadence()->saidOnTheScreen(), ['count' => $this->cadence()->seconds()]) }}
        </x-operator::note>
    @elseif ($this->done()->hasEnded)
        {{-- Not a failure: the client may well have let it go, and how full
             the machine is says whether it is still there. --}}
        <x-operator::emphasis>{{ __('stacks.let_go.no_outcome', ['download' => $this->downloadNamed()]) }}</x-operator::emphasis>
        <native:text>{{ __('stacks.let_go.no_outcome_action') }}</native:text>
        <x-operator::action label="{{ __('stacks.let_go.see_the_room') }}" :goes="$this->goes()->ofItself()->room()" />
    @elseif ($this->done()->wasRehearsed)
        {{-- A rehearsal, said to be one before anything else, and never
             worded as room freed: the client still holds all of it. --}}
        <x-operator::heading>{{ __('stacks.let_go.a_rehearsal') }}</x-operator::heading>
        <x-operator::emphasis>{{ __('stacks.let_go.rehearsed', ['download' => $this->done()->name]) }}</x-operator::emphasis>
        <native:text>{{ __('stacks.let_go.nothing_freed', ['figure' => $this->done()->size->figure, 'unit' => __($this->done()->size->unit)]) }}</native:text>
        <x-operator::action label="{{ __('stacks.let_go.see_the_room') }}" :goes="$this->goes()->ofItself()->room()" />
    @else
        <x-operator::emphasis>{{ __('stacks.let_go.let_go', ['download' => $this->done()->name]) }}</x-operator::emphasis>
        <native:text>{{ __('stacks.let_go.occupied', ['figure' => $this->done()->size->figure, 'unit' => __($this->done()->size->unit)]) }}</native:text>
        <x-operator::action label="{{ __('stacks.let_go.see_the_room') }}" :goes="$this->goes()->ofItself()->room()" />
    @endif

    <x-operator::quiet-action label="{{ __('health.ask_again') }}" tap="again()" />
@elseif ($this->answer()->isWorking)
    {{-- The stack answers the question as work, so the offer may not be
         in yet. Said as that, on the cadence it is read again at. --}}
    <x-operator::emphasis>{{ __('stacks.let_go.working_it_out', ['download' => $this->downloadNamed()]) }}</x-operator::emphasis>
    <x-operator::note>
        {{ __($this->cadence()->saidOnTheScreen(), ['count' => $this->cadence()->seconds()]) }}
    </x-operator::note>
    <x-operator::quiet-action label="{{ __('health.ask_again') }}" tap="again()" />
@elseif ($this->answer()->hasEnded)
    <x-operator::emphasis>{{ __('stacks.let_go.offer_ended', ['download' => $this->downloadNamed()]) }}</x-operator::emphasis>
    <x-operator::quiet-action label="{{ __('health.ask_again') }}" tap="again()" />
@else
    {{-- The offer: its own act, and what it costs, all of it before the yes.
         Nothing below has happened, and nothing is worded as though it had. --}}
    <x-operator::heading>{{ __('stacks.let_go.what_it_costs') }}</x-operator::heading>
    <native:text>{{ __('stacks.let_go.its_own_act') }}</native:text>

    <x-operator::entry>
        <native:text>{{ $this->answer()->download->name }}</native:text>
        <x-operator::note>{{ __('stacks.room.takes', ['figure' => $this->answer()->download->size->figure, 'unit' => __($this->answer()->download->size->unit)]) }}</x-operator::note>
        <x-operator::note>{{ __($this->answer()->download->standingSaid) }}</x-operator::note>

        @if ($this->answer()->download->ratioSaid !== '')
            <x-operator::emphasis>{{ __($this->answer()->download->ratioSaid, ['ratio' => $this->answer()->download->ratio]) }}</x-operator::emphasis>
        @endif

        @if ($this->answer()->download->consequence !== '')
            <x-operator::emphasis>{{ $this->answer()->download->consequence }}</x-operator::emphasis>
        @endif
    </x-operator::entry>

    <native:text>{{ $this->answer()->goes }}</native:text>

    <x-operator::action label="{{ __('stacks.let_go.stop_it') }}" tap="agree()" />
    <x-operator::quiet-action label="{{ __('health.ask_again') }}" tap="again()" />
@endif
</x-operator::content>
@else
    {{-- The stack would not say what stopping would cost, so there is
         nothing to agree to and nothing is offered. --}}
    <x-operator::what-stopped-the-reading
        :went="$this->answer()->went"
        :sign-in-goes-to="$this->goes()->signIn()"
    />
@endif

<x-operator::screen-closes :goes="$this->goes()" here="health" />
