@use('Modules\Stacks\Api\AStacksScreen')
@use('Modules\Operator\Api\HowTheColumnScrolls')
<x-operator::screen-opens :title="$this->title()->named()" :back="$this->hasAWayBack()" />

<x-operator::content :scrolls="HowTheColumnScrolls::PulledDownToAskAgain">

@if ($this->title()->title !== null)
    {{-- The title as the core answered it for this member: its poster, Play,
         and what it is. A part the core did not state is not drawn. Play is
         never hidden; where it cannot be pressed, the reason is beside it, in
         the core's words where the core gave one; where it was pressed and
         did not play, or stopped, the household's words say why. --}}
    <x-household::poster :poster="$this->title()->title->poster" />
    <x-design::action label="{{ __('household.title.play') }}" :answersTo="__('household.title.play_named', ['title' => $this->title()->title->poster->titled])" tap="play()" :disabled="! $this->title()->title->playing->canPlay" />
    <x-household::why-play-waits :playing="$this->title()->title->playing" />
    @if ($this->playingSaid !== '')
        <x-design::note>{{ __($this->playingSaid) }}</x-design::note>
    @endif
    @if ($this->title()->title->about !== '')
        <x-design::body>{{ $this->title()->title->about }}</x-design::body>
    @endif
    @if ($this->title()->title->runs !== '')
        <x-design::note>{{ __($this->title()->title->runs, $this->title()->title->runsFilling) }}</x-design::note>
    @endif
    @if ($this->title()->title->certificate !== '')
        <x-design::note>{{ __('household.title.certificate', ['certificate' => $this->title()->title->certificate]) }}</x-design::note>
    @endif
    @if ($this->title()->title->released !== '')
        <x-design::note>{{ __($this->title()->title->released, [...$this->title()->title->releasedFilling, 'month' => __($this->title()->title->releasedIn)]) }}</x-design::note>
    @endif
    <x-household::genres :genres="$this->title()->title->genres" />
    @forelse ($this->title()->title->seasons as $season)
        {{-- A series' seasons in the core's order, each episode with its own
             Play, never hidden either. --}}
        <x-design::heading>{{ $season->named }}</x-design::heading>
        @forelse ($season->episodes as $episode)
            <x-design::strong>{{ __($episode->headed, $episode->headedFilling) }}</x-design::strong>
            @if ($episode->runs !== '')
                <x-design::note>{{ __($episode->runs, $episode->runsFilling) }}</x-design::note>
            @endif
            @if ($episode->about !== '')
                <x-design::body>{{ $episode->about }}</x-design::body>
            @endif
            <x-design::action label="{{ __('household.title.play') }}" :answers-to="__('household.title.play_named', ['title' => $episode->titled])" tap="playTheEpisode('{{ $episode->id }}')" :disabled="! $episode->playing->canPlay" tone="tonal" />
            <x-household::why-play-waits :playing="$episode->playing" />
        @empty
            {{-- A season the core lists with no episodes in it says so. --}}
            <x-design::note>{{ __('household.title.no_episodes') }}</x-design::note>
        @endforelse
    @empty
        {{-- Anything but a series has no seasons, and draws nothing for them. --}}
    @endforelse
@elseif ($this->title()->isAbsent)
    {{-- The core says it is not on this member's shelf: outside their limits,
         or not in the house. The two are one answer, and Home is where to go. --}}
    <x-design::body>{{ __('household.title.absent') }}</x-design::body>
    <x-design::action label="{{ __('household.title.to_home') }}" :goes="$this->goes()->to(AStacksScreen::Shelf)" tone="tonal" />
@elseif ($this->title()->isSignedIn)
    <x-design::notice tone="unknown">
        <x-household::what-stood-in-the-way :met="$this->title()->met" :remedy="$this->title()->remedy" :filling="$this->title()->filling()" :in-the-stacks-words="$this->title()->isInTheStacksWords()" />
    </x-design::notice>

    <x-design::action label="{{ __('household.ask_again') }}" tap="askAgain()" />
    @if ($this->title()->isPutRightInTheAppsSettings())
        <x-design::action label="{{ __('connection.open_settings') }}" tap="openTheAppsSettings()" />
        @if ($this->theSettingsWouldNotOpen)
            <x-design::note>{{ __('connection.settings_would_not_open') }}</x-design::note>
        @endif
    @endif
@else
    <x-design::body>{{ __('household.signed_out') }}</x-design::body>
    <x-design::action label="{{ __('connection.sign_in') }}" :goes="$this->signIn()" />
@endif
</x-operator::content>

<x-household::screen-closes :goes="$this->goes()" :here="$this->itsTab()" />
