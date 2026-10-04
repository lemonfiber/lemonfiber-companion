<x-wayfinding::stack-opens :title="$this->stack()->name()->shown()" :stacks="$this->stacksToChooseFrom()" :choosing="$this->choosingAStack" />

<x-operator::content>
    <x-design::title>{{ __('stacks.pairing.heading') }}</x-design::title>

    {{-- What a code is and what it is not, before one is made: it adds this
         stack to another phone and signs nobody in. --}}
    <x-design::body>{{ __('stacks.pairing.what_it_is') }}</x-design::body>

    @if (! $this->going()->went->cameBack())
        <x-operator::what-stood-in-the-way
            :settings-would-not-open="$this->theSettingsWouldNotOpen"
            :went="$this->going()->went"
            :sign-in-goes-to="$this->goes()->signIn()"
            ask-again="make()"
        />
    @elseif ($this->going()->isWorking)
        <x-design::standing :said="__('stacks.invitation.working')" tone="working" />
    @elseif ($this->going()->code !== null)
        {{-- The line as a code, then the same line to type, then what to
             check and what to know. Nothing here copies it or passes it on:
             it is read off this screen or typed from it. --}}
        <x-design::card>
            <x-design::scannable :rows="$this->going()->code->squares" missing="{{ __('stacks.pairing.no_code') }}" />
            <x-design::body>{{ __('stacks.pairing.or_type') }}</x-design::body>
            <x-design::verbatim>{{ $this->going()->code->line }}</x-design::verbatim>
        </x-design::card>

        <x-design::body>{{ __('stacks.pairing.compare') }}</x-design::body>
        <x-design::verbatim>{{ $this->going()->code->compare }}</x-design::verbatim>

        <x-design::note>{{ __('stacks.pairing.until', ['until' => $this->going()->code->until]) }}</x-design::note>
        <x-design::note>{{ __('stacks.pairing.reaches', ['address' => $this->going()->code->address]) }}</x-design::note>
        @if ($this->going()->code->caution !== '')
            <x-design::note>{{ $this->going()->code->caution }}</x-design::note>
        @endif
        {{-- What replacing the certificate would cost, in the stack's words,
             and then how it is replaced, in this app's: at the machine. --}}
        <x-design::note>{{ $this->going()->code->replacing }}</x-design::note>
        <x-design::note>{{ __('stacks.pairing.replaced_at_the_machine') }}</x-design::note>
    @elseif ($this->going()->isCheckedDifferently)
        {{-- No line and no code: one the other phone could never check is
             not handed out. --}}
        <x-design::notice tone="trouble">
            <x-design::strong>{{ __('stacks.pairing.checked_differently') }}</x-design::strong>
        </x-design::notice>
    @elseif ($this->going()->hasExpired)
        <x-design::notice tone="unknown">
            <x-design::strong>{{ __('stacks.pairing.expired') }}</x-design::strong>
        </x-design::notice>
        <x-design::action label="{{ __('stacks.pairing.make_a_new_one') }}" tap="make()" />
    @else
        {{-- Nothing asked yet, or a refusal in the stack's own words: either
             way the next thing to do is to ask for a code. --}}
        @if ($this->going()->refused !== '')
            <x-operator::emphasis>{{ $this->going()->refused }}</x-operator::emphasis>
        @endif
        <x-design::action label="{{ __('stacks.pairing.make') }}" tap="make()" />
    @endif
</x-operator::content>

<x-operator::screen-closes :goes="$this->goes()" :here="$this->itsTab()" />
