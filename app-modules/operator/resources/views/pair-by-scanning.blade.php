<x-operator::screen-opens :title="__('navigation.pairing')" :back="$this->hasAWayBack()" />

<x-operator::content>
    <x-design::title>{{ __($this->headline(), ['stack' => $this->called()]) }}</x-design::title>
    <x-design::body>{{ __($this->supporting()) }}</x-design::body>

    @if ($this->went()->isPaired())
        {{-- Pairing is not signing in: the machine has been introduced and
             this device holds no session for it. So the way onwards is the
             password, not the report. --}}
        <x-design::action label="{{ __('connection.sign_in') }}" :goes="$this->onwardsTo()" />
    @endif

    @unless ($this->went()->isPaired())
        {{-- What the camera could not do, above the form that asks it again. --}}
        @if ($this->nothingWasScanned())
            <x-design::notice tone="unknown">
                <x-design::strong>{{ __($this->whyNothingCameBack()) }}</x-design::strong>
                <x-design::body>{{ __($this->remedyForTheCamera()) }}</x-design::body>
            </x-design::notice>
        @elseif ($this->codeWasUnreadable())
            <x-design::notice>
                <x-design::strong>{{ __('connection.scanned_code_is_unreadable') }}</x-design::strong>
                <x-design::body>{{ __('connection.scanned_code_is_unreadable_action') }}</x-design::body>
            </x-design::notice>
        @endif

        <x-design::card>
            <native:outlined-text-input
                native:model="called"
                label="{{ __('connection.name_label') }}"
                placeholder="{{ __('connection.name_placeholder') }}"
                supporting="{{ __('connection.name_this_stack') }}"
            />

            {{-- The app's own sentence and the typed road together, said before
                 the prompt rather than after a refusal: what the camera is for,
                 and what still works without it. An operator who reads this and
                 declines anyway has chosen the typed road knowingly. --}}
            <x-design::note>{{ __('device.camera_reason') }}</x-design::note>
            <x-design::note>{{ __('device.camera_alternative') }}</x-design::note>

            <x-design::action label="{{ __('connection.open_the_camera') }}" :disabled="! $this->mayScan()" tap="scan()" />
        </x-design::card>

        {{-- The second road, offered beside the first rather than after it has
             failed: a phone in a dark cupboard behind a rack is exactly where a
             camera is the wrong tool, and nobody wants to discover that twice.
             It is also what lets the list screen offer one road instead of two:
             the choice belongs where somebody is making it. --}}
        <x-design::link label="{{ __('connection.pair_by_typing') }}" :goes="$this->typingIsAt()" />
    @endunless

    {{-- The way out. On a first run the list is empty and these two roads are
         the only things on it, so a person who starts pairing and changes their
         mind — or whose camera is refused and who does not want to type a code
         either — has nowhere to go. The platform's own gesture may be there,
         and a screen that counts on it works on one handset and traps somebody
         on another. --}}
    <x-design::link label="{{ __('connection.back_to_your_stacks') }}" :goes="$this->theListIsAt()" />
</x-operator::content>
