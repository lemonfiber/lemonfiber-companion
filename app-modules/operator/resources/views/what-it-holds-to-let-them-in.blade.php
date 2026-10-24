@use('Modules\Stacks\Api\AStacksScreen')
@use('Modules\Kernel\Api\RevokingAGrant')
<x-wayfinding::stack-opens :back="$this->hasAWayBack()" :title="$this->stack()->name()->shown()" :stacks="$this->stacksToChooseFrom()" :choosing="$this->choosingAStack" />

@if ($this->answer()->went->cameBack())
<x-operator::content>
    <x-design::title>{{ __('stacks.credentials.heading') }}</x-design::title>

    @if ($this->revoking !== null)
        {{-- Ending one grant: read first, agreed to as that reading, followed to its end. --}}
        @if (! $this->howItIsGoing()->went->cameBack())
            <x-operator::what-stood-in-the-way
                :settings-would-not-open="$this->theSettingsWouldNotOpen"
                :went="$this->howItIsGoing()->went"
                :goes="$this->goes()"
            />
        @elseif ($this->howItIsGoing()->isWorking)
            <x-design::standing :said="__('stacks.credentials.revoke.working')" tone="working" />
        @elseif ($this->howItIsGoing()->hasEnded)
            <x-design::notice tone="unknown">
                <x-design::strong>{{ __('stacks.credentials.revoke.no_outcome') }}</x-design::strong>
            </x-design::notice>
        @elseif ($this->howItIsGoing()->refused !== null)
            <x-design::notice>
                <x-design::strong>{{ __('stacks.credentials.revoke.refused') }}</x-design::strong>
                <x-operator::refused-in-its-words :refused="$this->howItIsGoing()->refused" />
            </x-design::notice>
        @elseif ($this->howItIsGoing()->revoke !== null)
            <x-operator::the-revoke :revoke="$this->howItIsGoing()->revoke" :offer="$this->offered(RevokingAGrant::Revoke)" />
        @endif
        <x-operator::quiet-action :label="__('stacks.credentials.revoke.back')" tap="backToTheCredentials()" />
    @else
    @forelse ($this->answer()->held as $credential)
        <x-design::card>
            <x-design::strong>{{ $credential->name }}</x-design::strong>
            {{-- Each state in its own words: stale, invalid and rotating ask
                 for three different things and are never one warning. --}}
            <x-design::body>{{ __($credential->stateSaid) }}</x-design::body>
            {{-- Who made it decides who fixes it. --}}
            <x-design::note>{{ __($credential->originSaid) }}</x-design::note>

            <x-design::note>{{ __('stacks.credentials.used_by') }}</x-design::note>
            @forelse ($credential->consumers as $consumer)
                <x-design::body>{{ $consumer }}</x-design::body>
            @empty
                {{-- Nothing uses it, which is a reason to remove it rather
                     than a reason to hide it. --}}
                <x-design::body>{{ __('stacks.credentials.used_by_nothing') }}</x-design::body>
            @endforelse

            @if ($credential->advisory !== '')
                <x-design::note>{{ $credential->advisory }}</x-design::note>
            @endif

            @forelse ($credential->grants as $grant)
                <x-operator::the-grant :grant="$grant" :offer="$this->offered(RevokingAGrant::Revoke)" />
            @empty
                {{-- Granted to no plugin: what uses it is said above, and a grant adds nothing to say. --}}
            @endforelse
        </x-design::card>
    @empty
        <x-design::body>{{ __('stacks.credentials.none') }}</x-design::body>
    @endforelse

    @unless ($this->answer()->elsewhere === [])
        {{-- A grant of a credential the list does not hold is still a grant, and is never dropped. --}}
        <x-design::heading>{{ __('stacks.credentials.grants.elsewhere') }}</x-design::heading>
        @forelse ($this->answer()->elsewhere as $grant)
            <x-design::card>
                <x-design::strong>{{ __('stacks.credentials.grants.of', ['credential' => $grant->credential, 'whose' => $grant->whose]) }}</x-design::strong>
                <x-operator::the-grant :grant="$grant" :offer="$this->offered(RevokingAGrant::Revoke)" />
            </x-design::card>
        @empty
            {{-- Unreachable: this list is drawn only where it holds a grant. --}}
        @endforelse
    @endunless

    {{-- What keeping them in files protects against, and what it does not. --}}
    <x-design::heading>{{ __('stacks.credentials.protection.heading') }}</x-design::heading>
    <x-design::body>{{ $this->answer()->summary }}</x-design::body>

    <x-design::section :label="__('stacks.credentials.protection.against')">
        @forelse ($this->answer()->against as $threat)
            <x-design::row :headline="$threat" />
        @empty
            <x-design::row :headline="__('stacks.credentials.protection.nothing_listed')" />
        @endforelse
    </x-design::section>

    <x-design::section :label="__('stacks.credentials.protection.not_against')">
        @forelse ($this->answer()->notAgainst as $threat)
            <x-design::row :headline="$threat" />
        @empty
            <x-design::row :headline="__('stacks.credentials.protection.nothing_listed')" />
        @endforelse
    </x-design::section>

    {{-- Nothing here sets, changes or shows a value. --}}
    <x-design::note>{{ __('stacks.credentials.at_the_machine') }}</x-design::note>

    <x-design::action :label="__('health.ask_again')" tap="askAgain()" tone="tonal" />
    @endif
</x-operator::content>
@else
    <x-operator::what-stopped-the-reading
        :settings-would-not-open="$this->theSettingsWouldNotOpen"
        :went="$this->answer()->went"
        :goes="$this->goes()"
    />
@endif

<x-operator::screen-closes :goes="$this->goes()" :here="$this->itsTab()" :marks="$this->marks()" />
