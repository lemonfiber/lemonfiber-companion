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
            native:model="typed"
            label="{{ __('connection.code_label') }}"
            placeholder="{{ __('connection.code_placeholder') }}"
            supporting="{{ __($this->supportingTheCode()) }}"
            :error="$this->isUnreadable() || $this->hasExpired()"
            multiline
        />

        <native:outlined-text-input
            native:model="called"
            label="{{ __('connection.name_label') }}"
            placeholder="{{ __('connection.name_placeholder') }}"
            supporting="{{ __('connection.name_this_stack') }}"
        />

        @if ($this->isComparing())
            <native:text>{{ __('connection.compare_the_fingerprint') }}</native:text>
            <native:text class="text-xl font-bold">{{ $this->toCompare() }}</native:text>
        @endif

        <x-operator::action label="{{ __('connection.it_matches') }}" :disabled="! $this->mayPair()" tap="confirm()" />
    @endunless
    {{-- The way out. On a first run the list is empty and these two roads are
         the only things on it, so a person who starts pairing and changes their
         mind — or whose camera is refused and who does not want to type a code
         either — has nowhere to go. The platform's own gesture may be there,
         and a screen that counts on it works on one handset and traps somebody
         on another. --}}
    <x-operator::action label="{{ __('connection.back_to_your_stacks') }}" :goes="$this->theListIsAt()" />
</x-operator::screen>
