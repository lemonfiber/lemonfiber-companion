<x-operator::screen-opens :title="__('navigation.pairing')" />

<native:column class="w-full gap-4 px-6 py-4">
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

        {{-- The app's own sentence and the typed road together, said before the prompt rather than
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

        {{-- The second road, offered beside the first rather than after
             it has failed. It used to appear only once the camera came back
             with nothing or with something unreadable, which is the app making
             somebody fail before it admits the other way exists — and a phone
             in a dark cupboard behind a rack is exactly where a camera is the
             wrong tool and nobody wants to discover that twice.

             It is also what lets the list screen offer one road instead of two:
             the choice belongs where somebody is making it. --}}
        <x-operator::quiet-action label="{{ __('connection.pair_by_typing') }}" :goes="$this->typingIsAt()" />
    @endunless
    {{-- The way out. On a first run the list is empty and these two roads are
         the only things on it, so a person who starts pairing and changes their
         mind — or whose camera is refused and who does not want to type a code
         either — has nowhere to go. The platform's own gesture may be there,
         and a screen that counts on it works on one handset and traps somebody
         on another. --}}
    <x-operator::quiet-action label="{{ __('connection.back_to_your_stacks') }}" :goes="$this->theListIsAt()" />
</native:column>
