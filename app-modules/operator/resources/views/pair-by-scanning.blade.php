<x-operator::screen
    :title="__('navigation.pairing')"
>
    <x-operator::heading>{{ __($this->headline(), ['stack' => $this->called()]) }}</x-operator::heading>
    <native:text>{{ __($this->supporting()) }}</native:text>

    @if ($this->went()->isPaired())
        {{-- Pairing is not signing in: the machine has been introduced and
             this device holds no session for it. So the way onwards is the
             password, not the report. --}}
        <x-operator::action label="{{ __('connection.sign_in') }}" :goes="$this->onwardsTo()" />
    @endif

    @unless ($this->went()->isPaired())
        <native:outlined-text-input
            native:model="called"
            label="{{ __('connection.name_label') }}"
            placeholder="{{ __('connection.name_placeholder') }}"
            supporting="{{ __('connection.name_this_stack') }}"
        />

        {{-- N4-R2 and N4-R3 together, and said before the prompt rather than
             after a refusal: what the camera is for, and what still works
             without it. An operator who reads this and declines anyway has
             chosen the typed road knowingly. --}}
        <native:text>{{ __('device.camera_reason') }}</native:text>
        <native:text>{{ __('device.camera_alternative') }}</native:text>

        @if ($this->nothingWasScanned())
            <native:text>{{ __($this->whyNothingCameBack()) }}</native:text>
            <native:text>{{ __($this->remedyForTheCamera()) }}</native:text>
        @elseif ($this->codeWasUnreadable())
            <native:text>{{ __('connection.scanned_code_is_unreadable') }}</native:text>
            <native:text>{{ __('connection.scanned_code_is_unreadable_action') }}</native:text>
        @endif

        <x-operator::action label="{{ __('connection.open_the_camera') }}" :disabled="! $this->mayScan()" tap="scan()" />

        @if ($this->theTypedRoadWouldHelp())
            {{-- The sentence above says the code can be typed instead. This is
                 the way to do it: an instruction with no route is the app
                 telling somebody to do something it has not let them do, and on
                 a first run this screen is one of two things on an empty
                 list. --}}
            <x-operator::action label="{{ __('connection.pair_by_typing') }}" :goes="$this->typingIsAt()" />
        @endif
    @endunless
    {{-- The way out. On a first run the list is empty and these two roads are
         the only things on it, so a person who starts pairing and changes their
         mind — or whose camera is refused and who does not want to type a code
         either — has nowhere to go. The platform's own gesture may be there,
         and a screen that counts on it works on one handset and traps somebody
         on another. --}}
    <x-operator::action label="{{ __('connection.back_to_your_stacks') }}" :goes="$this->theListIsAt()" />
</x-operator::screen>
