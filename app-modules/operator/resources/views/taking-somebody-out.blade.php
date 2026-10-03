<x-wayfinding::stack-opens :title="$this->stack()->name()->shown()" :stacks="$this->stacksToChooseFrom()" :choosing="$this->choosingAStack" />

@if ($this->answer()->went->cameBack())
<x-operator::content>
@if ($this->answer()->namesNobody)
    {{-- Opened on nobody, so there is nothing to ask about and nothing to
         agree to. Who is in is where somebody is chosen. --}}
    <x-operator::emphasis>{{ __('stacks.removal.names_nobody') }}</x-operator::emphasis>
    <x-operator::action label="{{ __('stacks.removal.back_to_who_is_in') }}" :goes="$this->goes()->whoGetsIn()->invite()" />
@else
    <x-operator::heading>{{ __('stacks.removal.taking_out', ['name' => $this->answer()->name]) }}</x-operator::heading>

    @if ($this->answer()->isWorking)
        @if ($this->answer()->wasAgreed)
            <x-design::body>{{ __('stacks.removal.removing', ['name' => $this->answer()->name]) }}</x-design::body>
        @else
            <x-design::body>{{ __('stacks.removal.reading', ['name' => $this->answer()->name]) }}</x-design::body>
        @endif
    @elseif ($this->answer()->hasEnded)
        {{-- Not a failure and not a refusal: the stack has no outcome for it
             any more. After a yes that is not the same as it not having run,
             and it is said as what could not be read. --}}
        @if ($this->answer()->wasAgreed)
            <x-operator::emphasis>{{ __('stacks.removal.no_outcome_after_yes', ['name' => $this->answer()->name]) }}</x-operator::emphasis>
        @else
            <x-operator::emphasis>{{ __('stacks.removal.no_outcome', ['name' => $this->answer()->name]) }}</x-operator::emphasis>
        @endif
        <x-operator::action label="{{ __('stacks.removal.read_again', ['name' => $this->answer()->name]) }}" tap="again()" />
    @elseif ($this->answer()->refusal !== '')
        {{-- The stack's answer, with the name it was about, drawn as the
             reason it is rather than as something to try again. --}}
        <x-operator::emphasis>{{ __('stacks.removal.refused', ['name' => $this->answer()->name]) }}</x-operator::emphasis>
        <x-design::body>{{ $this->answer()->refusal }}</x-design::body>
    @elseif ($this->answer()->removal !== null)
        @if (! $this->answer()->removal->carriedOut)
            {{-- What it would cost, said to be only that before anything
                 else: nobody has been taken out. --}}
            <x-operator::emphasis>{{ __('stacks.removal.not_yet', ['name' => $this->answer()->removal->name]) }}</x-operator::emphasis>
        @endif

        {{-- How far it reached, in the stack's three words and never
             flattened into one: only everywhere is done. --}}
        @if ($this->answer()->removal->isDone)
            <x-operator::emphasis>{{ __($this->answer()->removal->revokedSaid, ['name' => $this->answer()->removal->name]) }}</x-operator::emphasis>
        @else
            <x-design::body>{{ __($this->answer()->removal->revokedSaid, ['name' => $this->answer()->removal->name]) }}</x-design::body>
        @endif

        {{-- What goes with them: their requests, destroyed rather than handed
             to anybody, and whether there is an account on the request
             service to take at all. --}}
        @if ($this->answer()->removal->carriedOut)
            <x-design::body>{{ trans_choice('stacks.removal.requests_went', $this->answer()->removal->requests) }}</x-design::body>
        @else
            <x-design::body>{{ trans_choice('stacks.removal.requests_go', $this->answer()->removal->requests) }}</x-design::body>
        @endif
        <x-design::body>{{ __($this->answer()->removal->asksSaid) }}</x-design::body>

        {{-- What the stack found, in its own words, before the yes and after
             it alike. --}}
        <x-operator::heading>{{ __('stacks.removal.found') }}</x-operator::heading>
        @forelse ($this->answer()->removal->findings as $finding)
            <x-design::body>{{ $finding }}</x-design::body>
        @empty
            <x-operator::note>{{ __('stacks.removal.found_nothing') }}</x-operator::note>
        @endforelse

        @if (! $this->answer()->removal->carriedOut)
            <x-operator::action label="{{ __('stacks.removal.take_them_out', ['name' => $this->answer()->removal->name]) }}" tap="agree()" />
        @elseif (! $this->answer()->removal->isDone)
            {{-- An account is still held on the request service, and the next
                 removal takes it: read what that would cost, and agree to it
                 again. --}}
            <x-operator::action label="{{ __('stacks.removal.read_again', ['name' => $this->answer()->removal->name]) }}" tap="again()" />
        @endif
    @endif

    <x-operator::quiet-action label="{{ __('stacks.removal.back_to_who_is_in') }}" :goes="$this->goes()->whoGetsIn()->invite()" />
@endif
</x-operator::content>
@else
    {{-- Asking, or asking after it, met something. After a yes, whether they
         were taken out could not be read, and that is said before what stood
         in the way. --}}
    @if ($this->answer()->wasAgreed)
        <x-operator::content>
            <x-operator::emphasis>{{ __('stacks.removal.unread_after_yes', ['name' => $this->answer()->name]) }}</x-operator::emphasis>
        </x-operator::content>
    @endif
    <x-operator::what-stopped-the-reading
        :settings-would-not-open="$this->theSettingsWouldNotOpen"
        :went="$this->answer()->went"
        :sign-in-goes-to="$this->goes()->signIn()"
    />
@endif

<x-operator::screen-closes :goes="$this->goes()" :here="$this->itsTab()" />
