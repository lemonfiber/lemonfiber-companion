@use('Modules\Stacks\Api\AStacksScreen')
<x-wayfinding::stack-opens :back="$this->hasAWayBack()" :title="$this->stack()->name()->shown()" :stacks="$this->stacksToChooseFrom()" :choosing="$this->choosingAStack" />

@if ($this->answer()->stands->went->cameBack())
<x-operator::content>
@if (! $this->answer()->namesACategory)
    {{-- Opened on no category the stack gives, so there is nothing to read
         and nothing to agree to. How full the machine is lists each one. --}}
    <x-operator::emphasis>{{ __('stacks.give_back.names_no_category') }}</x-operator::emphasis>
    <x-operator::action :label="__('stacks.let_go.see_the_room')" :goes="$this->goes()->to(AStacksScreen::Room)" />
@elseif ($this->wasAgreedTo())
    {{-- What the stack reclaimed, drawn from its report and never from the
         offer, so an offer cannot read as room returned. --}}
    @if (! $this->done()->stands->went->cameBack())
        <x-operator::what-stood-in-the-way
            :settings-would-not-open="$this->theSettingsWouldNotOpen"
            :went="$this->done()->stands->went"
            :goes="$this->goes()"
        />
    @elseif ($this->done()->stands->isWorking())
        <x-operator::emphasis>{{ __('stacks.give_back.giving_back') }}</x-operator::emphasis>
    @elseif ($this->done()->stands->hasEnded())
        <x-operator::why-it-ended :stands="$this->done()->stands" />
        <x-operator::emphasis>{{ __('stacks.give_back.no_outcome') }}</x-operator::emphasis>
        <x-operator::action :label="__('stacks.let_go.see_the_room')" :goes="$this->goes()->to(AStacksScreen::Room)" />
    @elseif ($this->done()->refused !== null)
        <x-operator::heading>{{ __('stacks.give_back.refused') }}</x-operator::heading>
        <x-operator::refused-in-its-words :refused="$this->done()->refused" />
        <x-operator::action :label="__('stacks.let_go.see_the_room')" :goes="$this->goes()->to(AStacksScreen::Room)" />
    @elseif ($this->done()->returned !== null)
        <x-operator::emphasis>{{ __('stacks.give_back.returned', ['figure' => $this->done()->returned->figure, 'unit' => __($this->done()->returned->unit)]) }}</x-operator::emphasis>
        <x-design::body>{{ trans_choice('stacks.give_back.went', $this->done()->went, ['count' => $this->done()->went]) }}</x-design::body>

        {{-- Each item left behind, by the stack's reason and never by where
             it is on the machine. --}}
        @forelse ($this->done()->left as $why)
            @if ($loop->first)
                <x-operator::heading>{{ trans_choice('stacks.give_back.left', count($this->done()->left), ['count' => count($this->done()->left)]) }}</x-operator::heading>
            @endif
            <x-operator::entry>
                <x-design::body>{{ $why }}</x-design::body>
            </x-operator::entry>
        @empty
            <x-design::body>{{ __('stacks.give_back.none_left') }}</x-design::body>
        @endforelse

        <x-operator::action :label="__('stacks.let_go.see_the_room')" :goes="$this->goes()->to(AStacksScreen::Room)" />
    @endif

    <x-operator::quiet-action :label="__('health.ask_again')" tap="askAgain()" />
@else
    {{-- The offer moved since the last yes: the stack removed nothing, says
         why in its words, and the offer below is the one read since. --}}
    @if ($this->movedOn !== null)
        <x-operator::heading>{{ __('stacks.give_back.moved') }}</x-operator::heading>
        <x-design::body>{{ __('stacks.give_back.moved_said') }}</x-design::body>
        <x-operator::refused-in-its-words :refused="$this->movedOn" />
    @endif

    @if ($this->answer()->category === null)
        <x-operator::emphasis>{{ __('stacks.give_back.not_on_offer') }}</x-operator::emphasis>
        <x-operator::action :label="__('stacks.let_go.see_the_room')" :goes="$this->goes()->to(AStacksScreen::Room)" />
    @else
        {{-- One confirmation, naming the category, its size, how many files it
             holds and that it cannot be undone. Nothing here has happened. --}}
        <x-operator::heading>{{ __('stacks.give_back.what_goes') }}</x-operator::heading>

        <x-operator::entry>
            <x-design::body>{{ __($this->answer()->category->aboutSaid) }}</x-design::body>
            <x-operator::note>{{ __('stacks.room.takes', ['figure' => $this->answer()->category->size->figure, 'unit' => __($this->answer()->category->size->unit)]) }}</x-operator::note>
            <x-operator::note>{{ trans_choice('stacks.give_back.files', $this->answer()->category->files, ['count' => $this->answer()->category->files]) }}</x-operator::note>
        </x-operator::entry>

        <x-operator::emphasis>{{ __('stacks.give_back.cannot_be_undone') }}</x-operator::emphasis>

        <x-operator::action :label="__('stacks.give_back.give_it_back')" tap="agree()" />
    @endif

    <x-operator::quiet-action :label="__('health.ask_again')" tap="askAgain()" />
@endif
</x-operator::content>
@else
    {{-- The stack would not say what it offers, so there is nothing to agree
         to and nothing is offered. --}}
    <x-operator::what-stopped-the-reading
        :settings-would-not-open="$this->theSettingsWouldNotOpen"
        :went="$this->answer()->stands->went"
        :goes="$this->goes()"
    />
@endif

<x-operator::screen-closes :goes="$this->goes()" :here="$this->itsTab()" :marks="$this->marks()" />
