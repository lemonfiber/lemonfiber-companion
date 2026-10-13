@use('Modules\Kernel\Api\ForgettingWhatIsKept')
@use('Modules\Stacks\Api\AStacksScreen')
<x-wayfinding::stack-opens :back="$this->hasAWayBack()" :title="$this->stack()->name()->shown()" :stacks="$this->stacksToChooseFrom()" :choosing="$this->choosingAStack" />

@if ($this->answer()->went->cameBack())
<x-operator::content>
    <x-design::title>{{ __('stacks.keeps.road_in') }}</x-design::title>

    <x-design::section :label="__('stacks.keeps.roots')">
        @forelse ($this->answer()->roots as $root)
            <x-design::row :headline="$root->what" :supporting="$root->where" />
        @empty
            <x-design::row :headline="__('stacks.keeps.no_roots')" />
        @endforelse
    </x-design::section>

    <x-design::heading>{{ __('stacks.keeps.kept') }}</x-design::heading>
    @forelse ($this->answer()->kept as $one)
        {{-- Whether it holds a secret is on every card, not only on the cards
             that do: a label shown only sometimes cannot be told apart from
             one somebody forgot. The card has no field for a value. --}}
        <x-design::card>
            <x-design::strong>{{ $one->what }}</x-design::strong>
            <x-design::verbatim>{{ $one->where }}</x-design::verbatim>
            <x-design::body>{{ $one->why }}</x-design::body>
            <x-design::note>{{ __($one->secretSaid) }}</x-design::note>
        </x-design::card>
    @empty
        <x-design::body>{{ __('stacks.keeps.nothing_kept') }}</x-design::body>
    @endforelse

    <x-design::section :label="__('stacks.keeps.beside')">
        @forelse ($this->answer()->beside as $one)
            <x-design::row :headline="$one->what" :supporting="$one->why" />
        @empty
            <x-design::row :headline="__('stacks.keeps.nothing_beside')" />
        @endforelse
    </x-design::section>

    @if ($this->copiesAreBeingRead())
        {{-- This frame read what the machine keeps, so the copies are read on
             the next one. --}}
        <x-design::section :label="__('stacks.keeps.copies')">
            <x-design::row :headline="__('stacks.keeps.reading_copies')" />
        </x-design::section>
        <x-design::the-next-frame />
    @elseif ($this->copies()->went->cameBack())
        <x-design::section :label="__('stacks.keeps.copies')">
            @forelse ($this->copies()->names as $name)
                {{-- Putting a copy back is offered for a copy the stack listed
                     and for nothing else, and it opens on what putting it back
                     would do rather than doing it. --}}
                <x-design::row
                    :headline="$name"
                    :supporting="__('stacks.keeps.put_back')"
                    :goes="$this->goes()->puttingBack($name)"
                    :answers-to="__('stacks.keeps.put_back_that', ['copy' => $name])"
                />
            @empty
                <x-design::row :headline="__('stacks.keeps.no_copies')" />
            @endforelse
        </x-design::section>
    @else
        {{-- A list that could not be read has its own sentence. Drawn as an
             empty list, it would say no copy had ever been taken. What stood
             in the way and the remedy both come from the obstacle, and are
             drawn here rather than by the shared component, because that one
             brings its own ask-again and this screen already has one. --}}
        <x-design::notice tone="unknown">
            <x-design::strong>{{ __('stacks.keeps.copies_unread') }}</x-design::strong>
            @if ($this->copies()->went->isSignedIn)
                <x-design::body>{{ __($this->copies()->went->met, $this->copies()->went->filling()) }}</x-design::body>
                <x-design::body>{{ __($this->copies()->went->remedy, $this->copies()->went->filling()) }}</x-design::body>
            @else
                <x-design::body>{{ __('connection.session_has_ended') }}</x-design::body>
            @endif
        </x-design::notice>
        @if ($this->copies()->went->isPutRightInTheAppsSettings())
            <x-design::action :label="__('connection.open_settings')" tap="openTheAppsSettings()" />
            @if ($this->theSettingsWouldNotOpen)
                <x-design::note>{{ __('connection.settings_would_not_open') }}</x-design::note>
            @endif
        @endif
        @unless ($this->copies()->went->isSignedIn)
            <x-design::action :label="__('connection.sign_in')" :goes="$this->goes()->to(AStacksScreen::SignIn)" />
        @endunless
    @endif

    <x-design::action :label="__('stacks.keeps.take_a_copy')" :goes="$this->goes()->to(AStacksScreen::Copy)" />

    <x-operator::heading>{{ __('stacks.forget.title') }}</x-operator::heading>
    <x-design::body>{{ __('stacks.forget.untouched') }}</x-design::body>
    <x-design::body>{{ __('stacks.forget.ends_this_session') }}</x-design::body>

    @if ($this->howForgettingIsGoing()->went->cameBack())
        @if ($this->howForgettingIsGoing()->isWorking)
            <x-design::standing :said="__('stacks.forget.working')" tone="working" />
        @elseif ($this->howForgettingIsGoing()->theWayInWent)
            <x-design::notice tone="unknown">
                <x-design::strong>{{ __('stacks.forget.way_in_went') }}</x-design::strong>
            </x-design::notice>
        @elseif ($this->howForgettingIsGoing()->hasEnded)
            <x-design::notice tone="unknown">
                <x-design::strong>{{ __('stacks.forget.no_outcome') }}</x-design::strong>
            </x-design::notice>
        @elseif ($this->howForgettingIsGoing()->refused !== null)
            <x-design::notice>
                <x-design::strong>{{ __('stacks.forget.refused') }}</x-design::strong>
                <x-operator::refused-in-its-words :refused="$this->howForgettingIsGoing()->refused" />
            </x-design::notice>
        @elseif ($this->howForgettingIsGoing()->wouldGo !== null)
            <x-design::notice>
                <x-design::strong>{{ __('stacks.forget.would_go') }}</x-design::strong>
            </x-design::notice>
            <x-design::section :label="__('stacks.keeps.roots')">
                @forelse ($this->howForgettingIsGoing()->wouldGo->roots as $root)
                    <x-design::row :headline="$root->what" :supporting="$root->where" />
                @empty
                    <x-design::row :headline="__('stacks.keeps.no_roots')" />
                @endforelse
            </x-design::section>
            <x-design::section :label="__('stacks.keeps.kept')">
                @forelse ($this->howForgettingIsGoing()->wouldGo->kept as $one)
                    <x-design::row :headline="$one->what" :supporting="$one->where" />
                @empty
                    <x-design::row :headline="__('stacks.keeps.nothing_kept')" />
                @endforelse
            </x-design::section>
            @if ($this->shownToForget !== null)
                <x-operator::offered-action :label="__('stacks.forget.forget_it')" tap="forgetEverything()" :offer="$this->offered(ForgettingWhatIsKept::Forget)" />
            @endif
        @elseif ($this->howForgettingIsGoing()->isDone)
            <x-design::notice>
                <x-design::strong>{{ __('stacks.forget.done') }}</x-design::strong>
            </x-design::notice>
            <x-design::section :label="__('stacks.forget.gone')">
                @forelse ($this->howForgettingIsGoing()->gone as $place)
                    <x-design::row :headline="$place" />
                @empty
                    <x-design::row :headline="__('stacks.forget.nothing_gone')" />
                @endforelse
            </x-design::section>
            @forelse ($this->howForgettingIsGoing()->left as $one)
                <x-design::row :headline="__('stacks.forget.left', ['where' => $one->where])" :supporting="$one->why" />
            @empty
                {{-- Nothing: every place went. --}}
            @endforelse
        @endif

        @if (! $this->howForgettingIsGoing()->isWorking && $this->shownToForget === null && ! $this->forgettingAgreed)
            <x-operator::offered-action :label="__('stacks.forget.what_would_go')" tap="whatWouldGo()" tone="tonal" :offer="$this->offered(ForgettingWhatIsKept::Forget)" />
        @endif
    @else
        <x-design::notice tone="unknown">
            <x-design::strong>{{ __('stacks.forget.unread') }}</x-design::strong>
            @if ($this->howForgettingIsGoing()->went->isSignedIn)
                <x-design::body>{{ __($this->howForgettingIsGoing()->went->met, $this->howForgettingIsGoing()->went->filling()) }}</x-design::body>
                <x-design::body>{{ __($this->howForgettingIsGoing()->went->remedy, $this->howForgettingIsGoing()->went->filling()) }}</x-design::body>
            @else
                <x-design::body>{{ __('connection.session_has_ended') }}</x-design::body>
            @endif
        </x-design::notice>
        @if ($this->howForgettingIsGoing()->went->isPutRightInTheAppsSettings())
            <x-design::action :label="__('connection.open_settings')" tap="openTheAppsSettings()" />
        @endif
        @unless ($this->howForgettingIsGoing()->went->isSignedIn)
            <x-design::action :label="__('connection.sign_in')" :goes="$this->goes()->to(AStacksScreen::SignIn)" />
        @endunless
    @endif

    <x-design::action :label="__('health.ask_again')" tap="askAgain()" tone="tonal" />
</x-operator::content>
@else
    <x-operator::what-stopped-the-reading
        :settings-would-not-open="$this->theSettingsWouldNotOpen"
        :went="$this->answer()->went"
        :goes="$this->goes()"
    />
@endif

<x-operator::screen-closes :goes="$this->goes()" :here="$this->itsTab()" :marks="$this->marks()" />
