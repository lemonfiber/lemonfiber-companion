<x-operator::screen-opens :title="$this->stack()->name()->shown()" />

@if ($this->answer()->went->cameBack())
<x-operator::content>
@if (! $this->answer()->wasAsked)
    {{-- What goes in is chosen here, before it is described again: the log
         window, the filenames, and each setting to reveal. --}}
    <x-operator::heading>{{ __('stacks.help.what_goes_in') }}</x-operator::heading>
    <native:text>{{ __('stacks.help.nothing_leaves') }}</native:text>

    <x-operator::emphasis>{{ trans_choice('stacks.help.lines', $this->lines) }}</x-operator::emphasis>
    @forelse ($this->windows() as $window)
        <x-operator::quiet-action label="{{ trans_choice('stacks.help.take_lines', $window) }}" tap="chooseLines({{ $window }})" />
    @empty
        {{-- The windows are this screen's own and there are always three. --}}
    @endforelse

    @if ($this->showsFilenames())
        <x-operator::emphasis>{{ __('stacks.help.filenames_shown') }}</x-operator::emphasis>
        <x-operator::quiet-action label="{{ __('stacks.help.replace_filenames') }}" tap="replaceFilenames()" />
    @else
        <x-operator::emphasis>{{ __('stacks.help.filenames_replaced') }}</x-operator::emphasis>
        <x-operator::quiet-action label="{{ __('stacks.help.show_filenames') }}" tap="showFilenames()" />
    @endif

    {{-- One setting at a time, by name, each on its own yes. There is no
         control here that reveals several, or all of them. --}}
    <x-operator::heading>{{ __('stacks.help.revealing') }}</x-operator::heading>
    <native:text>{{ __('stacks.help.revealing_is_publishing') }}</native:text>

    @forelse ($this->revealedNames() as $named)
        <x-operator::entry>
            <x-operator::emphasis>{{ __('stacks.help.reveals', ['name' => $named]) }}</x-operator::emphasis>
            <x-operator::quiet-action label="{{ __('stacks.help.take_back', ['name' => $named]) }}" tap="takeBack('{{ $named }}')" />
        </x-operator::entry>
    @empty
        <x-operator::note>{{ __('stacks.help.reveals_nothing') }}</x-operator::note>
    @endforelse

    @if ($this->revealing !== null)
        <x-operator::emphasis>{{ __('stacks.help.reveal_this', ['name' => $this->revealing]) }}</x-operator::emphasis>
        <x-operator::action label="{{ __('stacks.help.reveal', ['name' => $this->revealing]) }}" tap="reveal()" />
        <x-operator::quiet-action label="{{ __('stacks.help.keep_it_hidden') }}" tap="keepItHidden()" />
    @else
        <native:outlined-text-input
            native:model="naming"
            label="{{ __('stacks.help.setting_label') }}"
            placeholder="{{ __('stacks.help.setting_placeholder') }}"
        />
        <x-operator::quiet-action label="{{ __('stacks.help.name_it') }}" tap="nameASetting()" />
    @endif

    <x-operator::action label="{{ __('stacks.help.describe') }}" tap="describe()" />
@elseif ($this->answer()->isWorking)
    <x-operator::emphasis>{{ __('stacks.help.gathering') }}</x-operator::emphasis>
    <x-operator::note>
        {{ __($this->cadence()->saidOnTheScreen(), ['count' => $this->cadence()->seconds()]) }}
    </x-operator::note>
    <x-operator::quiet-action label="{{ __('health.ask_again') }}" tap="again()" />
@elseif ($this->answer()->hasEnded)
    {{-- Not a failure: the stack no longer says what became of it. --}}
    <x-operator::emphasis>{{ __('stacks.help.no_outcome') }}</x-operator::emphasis>
    <x-operator::quiet-action label="{{ __('stacks.help.start_over') }}" tap="startOver()" />
@elseif ($this->answer()->refused !== '')
    {{-- The stack's answer, in its words, and not a fault: asking the same
         again is refused the same way, so the one road offered is back to
         the choices. --}}
    <x-operator::heading>{{ __('stacks.help.refused') }}</x-operator::heading>
    <x-operator::emphasis>{{ $this->answer()->refused }}</x-operator::emphasis>
    <native:text>{{ __('stacks.help.refused_wrote_nothing') }}</native:text>
    <x-operator::quiet-action label="{{ __('stacks.help.start_over') }}" tap="startOver()" />
@elseif ($this->answer()->bundle !== null)
    @if ($this->answer()->isWritten)
        <x-operator::heading>{{ __('stacks.help.written') }}</x-operator::heading>
    @else
        {{-- A description, said to be one before anything else. --}}
        <x-operator::heading>{{ __('stacks.help.described') }}</x-operator::heading>
    @endif

    <native:text>{{ __($this->answer()->bundle->whereSaid, ['path' => $this->answer()->bundle->where]) }}</native:text>
    <native:text>{{ trans_choice('stacks.help.bytes', $this->answer()->bundle->bytes) }}</native:text>
    <native:text>{{ __('stacks.help.taken', ['at' => $this->answer()->bundle->takenAt, 'lemonfiber' => $this->answer()->bundle->lemonfiber, 'stack' => $this->answer()->bundle->stack]) }}</native:text>

    {{-- What it reveals comes first, where it is read before anything
         else. --}}
    @forelse ($this->answer()->bundle->revealed as $named)
        <x-operator::emphasis>{{ __('stacks.help.reveals', ['name' => $named]) }}</x-operator::emphasis>
    @empty
        <x-operator::note>{{ __('stacks.help.reveals_nothing') }}</x-operator::note>
    @endforelse

    <x-operator::note>{{ $this->answer()->bundle->window }}</x-operator::note>
    <x-operator::note>{{ __($this->answer()->bundle->filenamesSaid) }}</x-operator::note>

    {{-- What could not be collected is part of the bundle, said here rather
         than left to be noticed by its absence. --}}
    @forelse ($this->answer()->bundle->missing as $missing)
        <x-operator::emphasis>{{ __('stacks.help.missing', ['what' => $missing]) }}</x-operator::emphasis>
    @empty
        <x-operator::note>{{ __('stacks.help.nothing_missing') }}</x-operator::note>
    @endforelse

    {{-- Every file, whole, as the stack redacted it. --}}
    @forelse ($this->answer()->bundle->pieces as $piece)
        <x-operator::entry>
            <x-operator::heading>{{ $piece->name }}</x-operator::heading>
            <native:text>{{ $piece->body }}</native:text>
        </x-operator::entry>
    @empty
        <x-operator::note>{{ __('stacks.help.holds_no_files') }}</x-operator::note>
    @endforelse

    @if (! $this->answer()->isWritten)
        {{-- The second yes, on the description above and on nothing else. --}}
        <x-operator::action label="{{ __('stacks.help.write') }}" tap="write()" />
    @endif

    <x-operator::quiet-action label="{{ __('stacks.help.start_over') }}" tap="startOver()" />
@endif
</x-operator::content>
@else
    <x-operator::what-stopped-the-reading
        :went="$this->answer()->went"
        :sign-in-goes-to="$this->goes()->signIn()"
    />
@endif

<x-operator::screen-closes :goes="$this->goes()" here="health" />
