<x-operator::stack-opens :title="$this->stack()->name()->shown()" :stacks="$this->stacksToChooseFrom()" :choosing="$this->choosingAStack" />

<x-operator::content>
    <x-design::title>{{ __('stacks.handoff.title') }}</x-design::title>
    <x-design::note>{{ $this->named() }}</x-design::note>

    @if (! $this->going()->went->cameBack())
        <x-operator::what-stood-in-the-way
            :settings-would-not-open="$this->theSettingsWouldNotOpen"
            :went="$this->going()->went"
            :sign-in-goes-to="$this->goes()->signIn()"
            ask-again="show()"
        />
    @elseif ($this->going()->isWorking)
        <x-design::standing :said="__('stacks.invitation.working')" tone="working" />
    @elseif ($this->going()->handoff !== null)
        @if ($this->going()->handoff->heading !== '')
            <x-design::heading>{{ __($this->going()->handoff->heading) }}</x-design::heading>
        @endif
        @if ($this->going()->handoff->reason !== '')
            <x-design::body>{{ $this->going()->handoff->reason }}</x-design::body>
        @endif

        {{-- The address as a code, then the same address to type. It is the
             server's address and nothing more: whoever holds it still signs
             in as somebody. --}}
        @if ($this->going()->handoff->handsOver)
            <x-design::card>
                <x-design::scannable :rows="$this->going()->handoff->squares" missing="{{ __('stacks.invitation.no_code') }}" />
                <x-design::verbatim>{{ $this->going()->handoff->address }}</x-design::verbatim>
                @if ($this->going()->handoff->caution !== '')
                    <x-design::note>{{ $this->going()->handoff->caution }}</x-design::note>
                @endif
            </x-design::card>

            <x-design::heading>{{ __('stacks.handoff.on_their_device') }}</x-design::heading>
            @forelse ($this->going()->handoff->steps as $step)
                <x-design::body>{{ $step }}</x-design::body>
            @empty
                {{-- A code is never handed over without a step to take with
                     it; the address above is what there is. --}}
            @endforelse

            <x-design::heading>{{ __('stacks.handoff.which_app') }}</x-design::heading>
            @forelse ($this->going()->handoff->clients as $client)
                <x-design::card>
                    <x-design::strong>{{ $client->device }}</x-design::strong>
                    <x-design::body>{{ $client->client }}</x-design::body>
                    @if (! $client->openSource)
                        <x-design::note>{{ __('stacks.clients.not_open_source') }}</x-design::note>
                    @endif
                    @if ($client->link !== '')
                        <x-design::note>{{ __('stacks.handoff.opens_at_this_server', ['client' => $client->client]) }}</x-design::note>
                        <x-design::verbatim>{{ $client->link }}</x-design::verbatim>
                    @endif
                </x-design::card>
            @empty
                <x-design::body>{{ __('stacks.clients.no_devices') }}</x-design::body>
            @endforelse
        @endif

        @if ($this->going()->handoff->signedIn !== [])
            <x-design::heading>{{ __('stacks.handoff.signed_in_devices') }}</x-design::heading>
            @forelse ($this->going()->handoff->signedIn as $device)
                <x-design::card>
                    <x-design::strong>{{ $device->device }}</x-design::strong>
                    <x-design::body>{{ $device->client }}</x-design::body>
                    @if ($device->seen->said !== '')
                        <x-design::note>{{ __('stacks.handoff.last_seen', ['when' => trans_choice($device->seen->said, $device->seen->count)]) }}</x-design::note>
                    @endif
                </x-design::card>
            @empty
                {{-- Drawn only where some device is signed in. --}}
            @endforelse
        @endif

        @if ($this->going()->handoff->given->said !== '')
            <x-design::note>{{ __('stacks.handoff.first_given', ['when' => trans_choice($this->going()->handoff->given->said, $this->going()->handoff->given->count)]) }}</x-design::note>
        @endif

        {{-- What there is to do next, as the stack named it: asking again is
             a tap here, and anything else is a screen of its own. --}}
        @if ($this->going()->handoff->asksAgain)
            <x-design::action label="{{ __('health.ask_again') }}" tap="show()" />
        @endif
        @if ($this->going()->handoff->invites)
            <x-design::link label="{{ __('stacks.handoff.invite_them') }}" :goes="$this->goes()->whoGetsIn()->inviting($this->named())" />
        @endif
        @if ($this->going()->handoff->starts)
            <x-design::link label="{{ __('navigation.services') }}" :goes="$this->goes()->services()" />
        @endif
        @if ($this->going()->handoff->records)
            <x-design::link label="{{ __('navigation.menu.front_door') }}" :goes="$this->goes()->whoGetsIn()->frontDoor()" />
        @endif
    @else
        {{-- Nothing asked yet, or a refusal in the stack's own words: either
             way the next thing to do is to ask. --}}
        @if ($this->going()->refused !== '')
            <x-operator::emphasis>{{ $this->going()->refused }}</x-operator::emphasis>
        @endif
        <x-design::action label="{{ __('stacks.handoff.show') }}" tap="show()" />
    @endif
</x-operator::content>

<x-operator::screen-closes :goes="$this->goes()" :here="$this->itsTab()" />
