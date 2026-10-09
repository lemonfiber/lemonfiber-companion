<x-operator::screen-opens :title="__('household.tabs.home')" :back="$this->hasAWayBack()" />

<x-operator::content>

{{-- Home leads with what is theirs: what they were part-way through, what
     they asked for that has arrived, and what is on its way, each row drawn
     only where it holds something. Where those could not be asked for and the
     shelf answered, the space says so rather than reading as nothing there. --}}
@if ($this->whereTheyLeftOff()->cameBack)
    @forelse ($this->whereTheyLeftOff()->rows as $row)
        <x-household::shelf-row :row="$row" />
    @empty
        {{-- Nothing part-way through, which is said by drawing nothing. --}}
    @endforelse
@elseif ($this->leftOffWasStopped())
    <x-design::notice tone="quiet">
        <x-household::what-stood-in-the-way :met="$this->whereTheyLeftOff()->met" :remedy="$this->whereTheyLeftOff()->remedy" :filling="$this->whereTheyLeftOff()->filling()" :in-the-stacks-words="$this->whereTheyLeftOff()->isInTheStacksWords()" />
    </x-design::notice>
    @if (! $this->theirOwnWereStopped())
        {{-- Offered once: where their requests could not be asked for either,
             the Ask again below them asks for both. --}}
        <x-design::action label="{{ __('household.ask_again') }}" :answersTo="__('household.ask_again_for_yours')" tap="askAgain()" tone="tonal" />
    @endif
@endif

@if ($this->theirOwn()->cameBack)
    @forelse ($this->theirOwn()->rows as $row)
        <x-household::shelf-row :row="$row" />
    @empty
        {{-- Nothing of theirs is coming or here, which is said by drawing
             nothing: a row with nothing in it is not drawn. --}}
    @endforelse
@elseif ($this->theirOwnWereStopped())
    <x-design::notice tone="quiet">
        <x-household::what-stood-in-the-way :met="$this->theirOwn()->met" :remedy="$this->theirOwn()->remedy" :filling="$this->theirOwn()->filling()" :in-the-stacks-words="$this->theirOwn()->isInTheStacksWords()" />
    </x-design::notice>
    <x-design::action label="{{ __('household.ask_again') }}" :answersTo="__('household.ask_again_for_yours')" tap="askAgain()" tone="tonal" />
@endif

@if ($this->answer()->cameBack())
    {{-- The house's own: the newest title across the screen, then the shelf
         as rows of posters, what came in most recently and a row for each
         kind it holds. A poster carries its title, its kind and its year, and
         says all three to a screen reader at once. --}}
    @if ($this->answer()->hasAHero())
        <x-household::hero :poster="$this->answer()->hero" tap="play('{{ $this->answer()->hero->plays }}')" />
        @if ($this->playingSaid !== '')
            <x-design::note>{{ __($this->playingSaid) }}</x-design::note>
        @endif
    @endif
    @forelse ($this->answer()->rows as $row)
        <x-household::shelf-row :row="$row" />
    @empty
        {{-- Said in as many words. A member whose shelf holds nothing has an
             answer, and a blank frame is what a library nobody could reach
             looks like — the branch below is what keeps those apart, and this
             arm is only reached where the core answered. --}}
        <x-design::section>
            <x-design::row :headline="__('household.shelf_is_empty')" :supporting="__('household.shelf_is_empty_action')" />
        </x-design::section>
    @endforelse

    <x-design::action label="{{ __('household.ask_again') }}" tap="askAgain()" tone="tonal" />
@elseif ($this->answer()->isOutOfReach)
    {{-- Not an empty shelf, and drawn so it can never be mistaken for one. The
         library exists and could not be reached, which is the opposite thing
         to tell somebody about their own collection.

         The core's own sentences, in the core's own words. Whoever could not
         reach what is a fact about two machines, and a line written here would
         be this app guessing at which. --}}
    <x-design::notice tone="unknown">
        <x-design::strong>{{ __('household.shelf_is_out_of_reach') }}</x-design::strong>
        @forelse ($this->answer()->reasons as $reason)
            <x-design::body>{{ $reason }}</x-design::body>
        @empty
            {{-- The core said it could not, and said nothing about why. Better than
                 a blank frame, which reads as the shelf being empty. --}}
            <x-design::body>{{ __('household.shelf_is_out_of_reach_action') }}</x-design::body>
        @endforelse
    </x-design::notice>

    <x-design::action label="{{ __('household.ask_again') }}" tap="askAgain()" />
@elseif ($this->answer()->isSignedIn)
    {{-- What stood in the way and what to do about it, both off the obstacle,
         so this screen cannot describe a condition differently from the one
         beside it. --}}
    <x-design::notice tone="unknown">
        <x-household::what-stood-in-the-way :met="$this->answer()->met" :remedy="$this->answer()->remedy" :filling="$this->answer()->filling()" :in-the-stacks-words="$this->answer()->isInTheStacksWords()" />
    </x-design::notice>

    <x-design::action label="{{ __('household.ask_again') }}" tap="askAgain()" />
    @if ($this->answer()->isPutRightInTheAppsSettings())
        <x-design::action label="{{ __('connection.open_settings') }}" tap="openTheAppsSettings()" />
        @if ($this->theSettingsWouldNotOpen)
            <x-design::note>{{ __('connection.settings_would_not_open') }}</x-design::note>
        @endif
    @endif
@else
    {{-- The session has ended, so nothing was asked and there is nothing to
         report. The remedy is a screen rather than a sentence. --}}
    <x-design::body>{{ __('connection.session_has_ended') }}</x-design::body>
    <x-design::action label="{{ __('connection.sign_in') }}" :goes="$this->signIn()" />
@endif
</x-operator::content>

<x-household::screen-closes :goes="$this->goes()" :here="$this->itsTab()" />
