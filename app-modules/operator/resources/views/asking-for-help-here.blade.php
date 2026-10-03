<x-wayfinding::stack-opens :title="$this->stack()->name()->shown()" :stacks="$this->stacksToChooseFrom()" :choosing="$this->choosingAStack" />

@if ($this->answer()->went->cameBack())
<x-operator::content>
@if (! $this->answer()->wasAsked)
    {{-- What goes in is chosen here, before it is described again: the log
         window, the filenames, and each setting to reveal. Each choice is a
         card that says what it is now, over the chips that change it. --}}
    <x-design::heading>{{ __('stacks.help.what_goes_in') }}</x-design::heading>
    <x-design::body>{{ __('stacks.help.nothing_leaves') }}</x-design::body>

    <x-design::card>
        <x-design::strong>{{ trans_choice('stacks.help.lines', $this->window->value) }}</x-design::strong>
        <x-design::chips>
            @forelse ($this->windows() as $window)
                <x-design::chip label="{{ trans_choice('stacks.help.take_lines', $window->value) }}" tap="chooseLines({{ $window->value }})" :chosen="$this->takes($window)" />
            @empty
                {{-- The windows are this screen's own and there are always three. --}}
            @endforelse
        </x-design::chips>
    </x-design::card>

    <x-design::card>
        @if ($this->showsFilenames())
            <x-design::strong>{{ __('stacks.help.filenames_shown') }}</x-design::strong>
        @else
            <x-design::strong>{{ __('stacks.help.filenames_replaced') }}</x-design::strong>
        @endif
        <x-design::chips>
            <x-design::chip label="{{ __('stacks.help.replace_filenames') }}" tap="replaceFilenames()" :chosen="! $this->showsFilenames()" />
            <x-design::chip label="{{ __('stacks.help.show_filenames') }}" tap="showFilenames()" :chosen="$this->showsFilenames()" />
        </x-design::chips>
    </x-design::card>

    {{-- One setting at a time, by name, each on its own yes. There is no
         control here that reveals several, or all of them. --}}
    <x-design::heading>{{ __('stacks.help.revealing') }}</x-design::heading>
    <x-design::body>{{ __('stacks.help.revealing_is_publishing') }}</x-design::body>

    <x-design::card>
        @forelse ($this->revealedNames() as $named)
            <x-design::strong>{{ __('stacks.help.reveals', ['name' => $named]) }}</x-design::strong>
            <x-design::link label="{{ __('stacks.help.take_back', ['name' => $named]) }}" tap="takeBack('{{ $named }}')" />
        @empty
            <x-design::note>{{ __('stacks.help.reveals_nothing') }}</x-design::note>
        @endforelse
    </x-design::card>

    @if ($this->revealing !== null)
        {{-- The yes for the one setting named, with what it costs said before
             it is asked for. --}}
        <x-design::notice>
            <x-design::strong>{{ __('stacks.help.reveal_this', ['name' => $this->revealing]) }}</x-design::strong>
        </x-design::notice>
        <x-design::action label="{{ __('stacks.help.reveal', ['name' => $this->revealing]) }}" tap="reveal()" tone="tonal" />
        <x-design::action label="{{ __('stacks.help.keep_it_hidden') }}" tap="keepItHidden()" tone="tonal" />
    @else
        <x-design::card>
            <native:outlined-text-input
                native:model="naming"
                label="{{ __('stacks.help.setting_label') }}"
                placeholder="{{ __('stacks.help.setting_placeholder') }}"
            />
            <x-design::action label="{{ __('stacks.help.name_it') }}" tap="nameASetting()" tone="tonal" />
        </x-design::card>
    @endif

    <x-design::action label="{{ __('stacks.help.describe') }}" tap="describe()" />
@elseif ($this->answer()->isWorking)
    <x-design::standing
        :said="__('stacks.help.gathering')"
        tone="working"
    />
    <x-design::action label="{{ __('health.ask_again') }}" tap="again()" tone="tonal" />
@elseif ($this->answer()->hasEnded)
    {{-- Not a failure: the stack no longer says what became of it. --}}
    <x-design::notice tone="unknown">
        <x-design::strong>{{ __('stacks.help.no_outcome') }}</x-design::strong>
    </x-design::notice>
    <x-design::action label="{{ __('stacks.help.start_over') }}" tap="startOver()" />
@elseif ($this->answer()->refused !== null)
    {{-- The stack's answer, in its words, and not a fault: asking the same
         again is refused the same way, so the one road offered is back to
         the choices. What it names is where the credential sits, which is
         what to act on. --}}
    <x-design::notice>
        <x-design::strong>{{ __('stacks.help.refused') }}</x-design::strong>
        <x-operator::refused-in-its-words :refused="$this->answer()->refused" />
        <x-design::note>{{ __('stacks.help.refused_wrote_nothing') }}</x-design::note>
    </x-design::notice>
    <x-design::action label="{{ __('stacks.help.start_over') }}" tap="startOver()" />
@elseif ($this->answer()->bundle !== null)
    @if ($this->answer()->isWritten)
        <x-design::title>{{ __('stacks.help.written') }}</x-design::title>
    @else
        {{-- A description, said to be one before anything else. --}}
        <x-design::title>{{ __('stacks.help.described') }}</x-design::title>
    @endif

    <x-design::card>
        <x-design::body>{{ __($this->answer()->bundle->whereSaid, ['path' => $this->answer()->bundle->where]) }}</x-design::body>
        <x-design::note>{{ trans_choice('stacks.help.bytes', $this->answer()->bundle->bytes) }}</x-design::note>
        <x-design::note>{{ __('stacks.help.taken', ['at' => $this->answer()->bundle->takenAt, 'lemonfiber' => $this->answer()->bundle->lemonfiber, 'stack' => $this->answer()->bundle->stack]) }}</x-design::note>
    </x-design::card>

    {{-- What it holds. What it reveals comes first, where it is read before
         anything else; what could not be collected is part of the bundle,
         said here rather than left to be noticed by its absence. --}}
    <x-design::card>
        @forelse ($this->answer()->bundle->revealed as $named)
            <x-design::strong>{{ __('stacks.help.reveals', ['name' => $named]) }}</x-design::strong>
        @empty
            <x-design::note>{{ __('stacks.help.reveals_nothing') }}</x-design::note>
        @endforelse

        <x-design::note>{{ $this->answer()->bundle->window }}</x-design::note>
        <x-design::note>{{ __($this->answer()->bundle->filenamesSaid) }}</x-design::note>

        @forelse ($this->answer()->bundle->missing as $missing)
            <x-design::strong>{{ __('stacks.help.missing', ['what' => $missing]) }}</x-design::strong>
        @empty
            <x-design::note>{{ __('stacks.help.nothing_missing') }}</x-design::note>
        @endforelse
    </x-design::card>

    {{-- Every file, whole, as the stack redacted it. --}}
    @forelse ($this->answer()->bundle->pieces as $piece)
        <x-design::card>
            <x-design::strong>{{ $piece->name }}</x-design::strong>
            <x-design::verbatim>{{ $piece->body }}</x-design::verbatim>
        </x-design::card>
    @empty
        <x-design::body>{{ __('stacks.help.holds_no_files') }}</x-design::body>
    @endforelse

    @if ($this->answer()->isWritten)
        {{-- The operator's own act, below everything the bundle holds: the
             file goes to the phone's own sharing and they choose where it
             goes. This app sends it nowhere. --}}
        <x-design::action label="{{ __('stacks.help.hand_over') }}" tap="handOver()" />
    @else
        {{-- The second yes, on the description above and on nothing else. --}}
        <x-design::action label="{{ __('stacks.help.write') }}" tap="write()" />
    @endif

    @if ($this->handing !== '')
        <x-design::card>
            <x-design::strong>{{ __($this->handing) }}</x-design::strong>
            <x-design::body>{{ __($this->handingLeaves) }}</x-design::body>
        </x-design::card>
    @endif

    <x-design::action label="{{ __('stacks.help.start_over') }}" tap="startOver()" tone="tonal" />
@endif
</x-operator::content>
@else
    <x-operator::what-stopped-the-reading
        :settings-would-not-open="$this->theSettingsWouldNotOpen"
        :went="$this->answer()->went"
        :sign-in-goes-to="$this->goes()->signIn()"
    />
@endif

<x-operator::screen-closes :goes="$this->goes()" :here="$this->itsTab()" />
