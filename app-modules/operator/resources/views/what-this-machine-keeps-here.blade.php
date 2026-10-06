@use('Modules\Stacks\Api\AStacksScreen')
<x-wayfinding::stack-opens :title="$this->stack()->name()->shown()" :stacks="$this->stacksToChooseFrom()" :choosing="$this->choosingAStack" />

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
            <x-design::action label="{{ __('connection.open_settings') }}" tap="openTheAppsSettings()" />
            @if ($this->theSettingsWouldNotOpen)
                <x-design::note>{{ __('connection.settings_would_not_open') }}</x-design::note>
            @endif
        @endif
        @unless ($this->copies()->went->isSignedIn)
            <x-design::action label="{{ __('connection.sign_in') }}" :goes="$this->goes()->to(AStacksScreen::SignIn)" />
        @endunless
    @endif

    <x-design::action label="{{ __('stacks.keeps.take_a_copy') }}" :goes="$this->goes()->to(AStacksScreen::Copy)" />

    <x-design::action label="{{ __('health.ask_again') }}" tap="askAgain()" tone="tonal" />
</x-operator::content>
@else
    <x-operator::what-stopped-the-reading
        :settings-would-not-open="$this->theSettingsWouldNotOpen"
        :went="$this->answer()->went"
        :goes="$this->goes()"
    />
@endif

<x-operator::screen-closes :goes="$this->goes()" :here="$this->itsTab()" :marks="$this->marks()" />
