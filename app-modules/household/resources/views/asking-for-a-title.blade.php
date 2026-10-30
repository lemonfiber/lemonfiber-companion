@use('Modules\Stacks\Api\AStacksScreen')
<x-operator::screen-opens :title="$this->title()?->title() ?? __('household.found.heading')" :back="$this->hasAWayBack()" />

<x-operator::content>

@if ($this->title() === null)
    {{-- Opened with nothing carried from a search, so there is nothing to say
         of the title and nothing to ask for. --}}
    <x-design::body>{{ __('household.found.from_search') }}</x-design::body>
    <x-design::action :label="__('household.found.to_search')" :goes="$this->goes()->to(AStacksScreen::Search)" tone="tonal" />
@else
    @if ($this->pictured() !== null)
    <native:image src="{{ $this->pictured() }}" class="w-32 aspect-[2/3] rounded object-cover" />
    @endif
    <x-design::title>{{ $this->shown()->titled }}</x-design::title>
    <x-design::note>{{ __($this->shown()->line, ['year' => $this->shown()->year, 'kind' => __($this->shown()->kind), 'stands' => __($this->shown()->stands)]) }}</x-design::note>

    @if ($this->asked !== null && $this->asked->cameBack())
        {{-- The core's own sentence for what the ask became. --}}
        <x-design::body>{{ $this->asked->answer->said }}</x-design::body>
        <x-design::action :label="__('household.found.to_requests')" :goes="$this->requests()" tone="tonal" />
    @elseif (! $this->title()->stands()->mayBeAskedFor())
        {{-- Not askable: where it stands is the action, shown and not usable. --}}
        <x-design::action :label="__($this->shown()->stands)" tap="ask()" :disabled="true" />
    @elseif (! $this->rehearsal()->isSignedIn() || ($this->asked !== null && ! $this->asked->isSignedIn()))
        <x-design::body>{{ __('household.signed_out') }}</x-design::body>
        <x-design::action :label="__('connection.sign_in')" :goes="$this->signIn()" />
    @else
        @if ($this->rehearsal()->cameBack())
            {{-- The core's sentence on whether it waits for approval and what it
                 leaves of their allowance, before they decide. --}}
            <x-design::body>{{ $this->rehearsal()->answer->said }}</x-design::body>
        @else
            <x-design::notice tone="unknown">
                <x-household::what-stood-in-the-way :stopped="$this->rehearsal()->stopped" />
            </x-design::notice>
        @endif

        <x-design::action :label="__('household.found.ask')" :answersTo="__('household.found.ask_named', ['title' => $this->title()->title()])" tap="ask()" :disabled="! $this->rehearsal()->cameBack()" />

        @if ($this->asked !== null && ! $this->asked->cameBack())
            {{-- The ask may or may not have reached the house. Asking again is
                 the same ask, and Requests says whether it arrived. --}}
            <x-design::notice tone="unknown">
                <x-household::what-stood-in-the-way :stopped="$this->asked->stopped" />
            </x-design::notice>
            <x-design::body>{{ __('household.found.may_have_gone') }}</x-design::body>
            <x-design::action :label="__('household.found.to_requests')" :goes="$this->requests()" tone="tonal" />
        @endif

        <x-design::action :label="__('household.ask_again')" tap="askAgain()" tone="tonal" />
    @endif
@endif
</x-operator::content>

<x-household::screen-closes :goes="$this->goes()" :here="$this->itsTab()" />
