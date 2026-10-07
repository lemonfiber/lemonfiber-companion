@use('Modules\Kernel\Api\TakingItOff')
@use('Modules\Stacks\Api\AStacksScreen')
<x-wayfinding::stack-opens :title="$this->stack()->name()->shown()" :stacks="$this->stacksToChooseFrom()" :choosing="$this->choosingAStack" />

@if ($this->answer()->went->cameBack())
<x-operator::content>
    <x-operator::heading>{{ __('uninstall.heading') }}</x-operator::heading>

@if (! $this->answer()->chosen)
    {{-- Four decisions, each read on its own before anything is agreed to.
         The library is apart from the other three, because it is never
         taken along with another. --}}
    <x-design::body>{{ __('uninstall.four') }}</x-design::body>
    <x-operator::action label="{{ __('uninstall.read', ['tier' => __('uninstall.tier.stop')]) }}" tap="choose('stop')" />
    <x-operator::action label="{{ __('uninstall.read', ['tier' => __('uninstall.tier.services')]) }}" tap="choose('services')" />
    <x-operator::action label="{{ __('uninstall.read', ['tier' => __('uninstall.tier.configuration')]) }}" tap="choose('configuration')" />

    <x-operator::heading>{{ __('uninstall.the_library') }}</x-operator::heading>
    <x-operator::note>{{ __('uninstall.the_library_alone') }}</x-operator::note>
    <x-operator::action label="{{ __('uninstall.read', ['tier' => __('uninstall.tier.media')]) }}" tap="choose('media')" />
@elseif ($this->answer()->isWorking)
    <x-design::body>{{ __('uninstall.removing') }}</x-design::body>
@elseif ($this->answer()->hasEnded)
    {{-- Not a failure and not a refusal: the stack has no outcome for the
         yes any more, which is not the same as it not having happened. --}}
    <x-operator::emphasis>{{ __('uninstall.no_outcome') }}</x-operator::emphasis>
    @if ($this->answer()->endsThisSession)
        <x-design::body>{{ __('uninstall.after_configuration') }}</x-design::body>
    @endif
    <x-operator::action label="{{ __('uninstall.read_again') }}" tap="askAgain()" />
@elseif ($this->answer()->refusal !== '')
    {{-- The stack's answer, drawn as the reason it is rather than as
         something to try again. --}}
    <x-operator::emphasis>{{ __('uninstall.refused') }}</x-operator::emphasis>
    <x-design::body>{{ $this->answer()->refusal }}</x-design::body>
    <x-operator::action label="{{ __('uninstall.choose_again') }}" tap="chooseAgain()" />
@elseif ($this->answer()->reading === null)
    {{-- The yes met something on the way, and it takes what admits this
         app: the machine may no longer answer it, which is said as that and
         never as a credential refused. --}}
    <x-operator::emphasis>{{ __('uninstall.unread_after_yes') }}</x-operator::emphasis>
    <x-design::body>{{ __('uninstall.after_configuration') }}</x-design::body>
@else
    <x-operator::heading>{{ __($this->answer()->reading->tierSaid) }}</x-operator::heading>

    @if ($this->answer()->did !== null)
        {{-- What the removal did, drawn from the stack's report and apart from
             how much of the reading could be read. --}}
        <x-operator::emphasis>{{ __($this->answer()->did->saidAs) }}</x-operator::emphasis>
        @if ($this->answer()->did->isFinished)
            <x-operator::heading>{{ __('uninstall.left') }}</x-operator::heading>
            @forelse ($this->answer()->did->left as $left)
                <x-operator::entry>
                    <x-operator::emphasis>{{ $left->what }}</x-operator::emphasis>
                    <x-design::body>{{ __('uninstall.left_why', ['why' => $left->why]) }}</x-design::body>
                    <x-operator::note>{{ __('uninstall.by_hand', ['how' => $left->byHand]) }}</x-operator::note>
                </x-operator::entry>
            @empty
                <x-operator::note>{{ __('uninstall.nothing_left') }}</x-operator::note>
            @endforelse

            <x-operator::heading>{{ __('uninstall.credentials') }}</x-operator::heading>
            @forelse ($this->answer()->did->credentials as $credential)
                <x-design::body>{{ $credential }}</x-design::body>
            @empty
                <x-operator::note>{{ __('uninstall.no_credentials') }}</x-operator::note>
            @endforelse

            <x-operator::heading>{{ __('uninstall.gone') }}</x-operator::heading>
            @forelse ($this->answer()->did->gone as $gone)
                <x-design::body>{{ $gone }}</x-design::body>
            @empty
                <x-operator::note>{{ __('uninstall.nothing_gone') }}</x-operator::note>
            @endforelse
        @endif

        @if ($this->answer()->endsThisSession)
            <x-operator::emphasis>{{ __('uninstall.after_configuration') }}</x-operator::emphasis>
        @else
            <x-operator::quiet-action label="{{ __('uninstall.choose_again') }}" tap="chooseAgain()" />
        @endif
    @else
        @if ($this->answer()->reading->takesTheLibrary)
            <x-operator::emphasis>{{ __('uninstall.the_library_alone') }}</x-operator::emphasis>
        @endif
        {{-- How much of it could be read, straight after: a list that is
             short says so, and says what could not be read. --}}
        @if ($this->answer()->reading->isComplete)
            <x-design::body>{{ __('uninstall.complete') }}</x-design::body>
        @else
            <x-operator::emphasis>{{ __('uninstall.incomplete') }}</x-operator::emphasis>
        @endif
        @if ($this->answer()->reading->unread !== [])
            <x-operator::heading>{{ __('uninstall.unread') }}</x-operator::heading>
        @endif
        @forelse ($this->answer()->reading->unread as $unread)
            <x-design::body>{{ $unread }}</x-design::body>
        @empty
            {{-- Every source answered, which the line above says. --}}
        @endforelse

        <x-operator::note>{{ __('uninstall.removes') }}</x-operator::note>
        <x-design::body>{{ $this->answer()->reading->removes }}</x-design::body>
        <x-operator::note>{{ __('uninstall.keeps') }}</x-operator::note>
        <x-design::body>{{ $this->answer()->reading->keeps }}</x-design::body>

        {{-- What goes, line by line, a credential marked and never shown. --}}
        <x-operator::heading>{{ __('uninstall.going') }}</x-operator::heading>
        @forelse ($this->answer()->reading->going as $line)
            <x-operator::entry>
                <x-operator::emphasis>{{ $line->name }}</x-operator::emphasis>
                <x-design::body>{{ $line->what }}</x-design::body>
                <x-operator::note>{{ __($line->sortSaid) }}</x-operator::note>
                @if ($line->size !== null)
                    <x-operator::note>{{ __('uninstall.takes', ['figure' => $line->size->figure, 'unit' => __($line->size->unit)]) }}</x-operator::note>
                @else
                    <x-operator::note>{{ __('uninstall.size_unread') }}</x-operator::note>
                @endif
                @if ($line->holdsACredential)
                    <x-operator::note>{{ __('uninstall.holds_a_credential') }}</x-operator::note>
                @endif
            </x-operator::entry>
        @empty
            <x-operator::note>{{ __('uninstall.nothing_going') }}</x-operator::note>
        @endforelse
        @if ($this->answer()->reading->isComplete)
            <x-design::body>{{ __('uninstall.frees', ['figure' => $this->answer()->reading->frees->figure, 'unit' => __($this->answer()->reading->frees->unit)]) }}</x-design::body>
        @else
            <x-design::body>{{ __('uninstall.frees_as_read', ['figure' => $this->answer()->reading->frees->figure, 'unit' => __($this->answer()->reading->frees->unit)]) }}</x-design::body>
        @endif

        {{-- What is kept, apart from what goes and not counted in it. --}}
        <x-operator::heading>{{ __('uninstall.kept') }}</x-operator::heading>
        @forelse ($this->answer()->reading->kept as $line)
            <x-operator::entry>
                <x-operator::emphasis>{{ $line->name }}</x-operator::emphasis>
                <x-design::body>{{ $line->whyKept }}</x-design::body>
                <x-operator::note>{{ $line->what }}</x-operator::note>
            </x-operator::entry>
        @empty
            <x-operator::note>{{ __('uninstall.nothing_kept') }}</x-operator::note>
        @endforelse

        {{-- What is not lemonfiber's, apart, with how much and how many. --}}
        <x-operator::heading>{{ __('uninstall.foreign') }}</x-operator::heading>
        @if ($this->answer()->reading->foreign !== [])
            <x-operator::note>{{ __('uninstall.foreign_is') }}</x-operator::note>
        @endif
        @forelse ($this->answer()->reading->foreign as $foreign)
            <x-design::body>{{ trans_choice('uninstall.foreign_line', $foreign->files, ['at' => $foreign->at, 'figure' => $foreign->size->figure, 'unit' => __($foreign->size->unit)]) }}</x-design::body>
        @empty
            <x-operator::note>{{ __('uninstall.nothing_foreign') }}</x-operator::note>
        @endforelse

        {{-- What lemonfiber cannot take, found or not, with how by hand. --}}
        <x-operator::heading>{{ __('uninstall.outside') }}</x-operator::heading>
        @if ($this->answer()->reading->outside !== [])
            <x-operator::note>{{ __('uninstall.outside_is') }}</x-operator::note>
        @endif
        @forelse ($this->answer()->reading->outside as $outside)
            <x-operator::entry>
                <x-operator::emphasis>{{ $outside->what }}</x-operator::emphasis>
                <x-design::body>{{ $outside->why }}</x-design::body>
                <x-operator::note>{{ __($outside->foundSaid) }}</x-operator::note>
                <x-operator::note>{{ __('uninstall.by_hand', ['how' => $outside->byHand]) }}</x-operator::note>
            </x-operator::entry>
        @empty
            <x-operator::note>{{ __('uninstall.nothing_outside') }}</x-operator::note>
        @endforelse

        @if ($this->answer()->reading->endsThisSession)
            {{-- What taking the configuration does before it is agreed to: the
                 copy the stack says it takes first, and this app's way in. --}}
            @if ($this->answer()->reading->copyFirst !== '')
                <x-design::body>{{ __('uninstall.copy_first', ['said' => $this->answer()->reading->copyFirst]) }}</x-design::body>
            @else
                <x-design::body>{{ __('uninstall.no_copy_first') }}</x-design::body>
                <x-operator::quiet-action label="{{ __('uninstall.take_a_copy') }}" :goes="$this->goes()->to(AStacksScreen::Copy)" />
            @endif
            <x-operator::emphasis>{{ __('uninstall.ends_this_session') }}</x-operator::emphasis>
        @endif

        @if ($this->answer()->reading->volume !== '')
            {{-- A network share or a drive that unplugs, said before the yes
                 and acknowledged apart from it. --}}
            <x-operator::heading>{{ __('uninstall.volume') }}</x-operator::heading>
            <x-design::body>{{ $this->answer()->reading->volume }}</x-design::body>
            @if ($this->stillToAcknowledge())
                <x-operator::action label="{{ __('uninstall.acknowledge_the_volume') }}" tap="acknowledgeTheVolume()" />
            @else
                <x-operator::note>{{ __('uninstall.volume_acknowledged') }}</x-operator::note>
            @endif
        @endif

        {{-- What is still coming down, each with how far along, and the yes
             as two where there is any: waiting, and going ahead. --}}
        <x-operator::heading>{{ __('uninstall.coming') }}</x-operator::heading>
        @forelse ($this->answer()->reading->coming as $coming)
            <x-design::body>{{ __('uninstall.coming_line', ['name' => $coming->name, 'progress' => $coming->progress]) }}</x-design::body>
        @empty
            <x-operator::note>{{ __('uninstall.nothing_coming') }}</x-operator::note>
        @endforelse

        @if ($this->answer()->reading->coming !== [])
            <x-operator::note>{{ __('uninstall.interrupts') }}</x-operator::note>
            <x-operator::offered-action
                label="{{ __('uninstall.wait', ['agree' => __($this->answer()->reading->agreeSaid, ['figure' => $this->answer()->reading->frees->figure, 'unit' => __($this->answer()->reading->frees->unit)])]) }}"
                tap="waitThenGo()"
                :offer="$this->offered(TakingItOff::TakeItOff)"
                :disabled="$this->stillToAcknowledge()"
            />
            <x-operator::offered-action
                label="{{ __('uninstall.go_ahead_now', ['agree' => __($this->answer()->reading->agreeSaid, ['figure' => $this->answer()->reading->frees->figure, 'unit' => __($this->answer()->reading->frees->unit)])]) }}"
                tap="goAhead()"
                :offer="$this->offered(TakingItOff::TakeItOff)"
                :disabled="$this->stillToAcknowledge()"
            />
        @else
            <x-operator::offered-action
                label="{{ __($this->answer()->reading->agreeSaid, ['figure' => $this->answer()->reading->frees->figure, 'unit' => __($this->answer()->reading->frees->unit)]) }}"
                tap="goAhead()"
                :offer="$this->offered(TakingItOff::TakeItOff)"
                :disabled="$this->stillToAcknowledge()"
            />
        @endif

        <x-operator::quiet-action label="{{ __('uninstall.choose_again') }}" tap="chooseAgain()" />
    @endif
@endif
</x-operator::content>
@else
    {{-- Reading the removal, or following the yes, met something. After a
         yes, whether it happened could not be read, and that is said first. --}}
    @if ($this->answer()->wasAgreed)
        <x-operator::content>
            <x-operator::emphasis>{{ __('uninstall.unread_after_yes') }}</x-operator::emphasis>
        </x-operator::content>
    @endif
    <x-operator::what-stopped-the-reading
        :settings-would-not-open="$this->theSettingsWouldNotOpen"
        :went="$this->answer()->went"
        :goes="$this->goes()"
    />
@endif

<x-operator::screen-closes :goes="$this->goes()" :here="$this->itsTab()" :marks="$this->marks()" />
