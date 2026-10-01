<x-operator::screen-opens :title="__('navigation.pairing')" />

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
        <x-design::card>
            <native:outlined-text-input
                native:model="typed"
                label="{{ __('connection.code_label') }}"
                placeholder="{{ __('connection.code_placeholder') }}"
                supporting="{{ __($this->supportingTheCode()) }}"
                :error="$this->isUnreadable() || $this->hasExpired()"
                autocorrect="off"
                autocapitalize="none"
            />

            <native:outlined-text-input
                native:model="called"
                label="{{ __('connection.name_label') }}"
                placeholder="{{ __('connection.name_placeholder') }}"
                supporting="{{ __('connection.name_this_stack') }}"
            />

            {{-- The fingerprint the code carries, drawn as the machine wrote it,
                 above the yes that trusts it. --}}
            @if ($this->isComparing())
                <x-design::body>{{ __('connection.compare_the_fingerprint') }}</x-design::body>
                <x-design::verbatim>{{ $this->toCompare() }}</x-design::verbatim>
            @endif

            <x-design::action label="{{ __('connection.it_matches') }}" :disabled="! $this->mayPair()" tap="confirm()" />
        </x-design::card>
    @endunless

    {{-- The way out. On a first run the list is empty and these two roads are
         the only things on it, so a person who starts pairing and changes their
         mind — or whose camera is refused and who does not want to type a code
         either — has nowhere to go. The platform's own gesture may be there,
         and a screen that counts on it works on one handset and traps somebody
         on another. --}}
    <x-design::link label="{{ __('connection.back_to_your_stacks') }}" :goes="$this->theListIsAt()" />
</x-operator::content>
