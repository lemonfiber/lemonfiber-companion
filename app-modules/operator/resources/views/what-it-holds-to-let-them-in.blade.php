@use('Modules\Stacks\Api\AStacksScreen')
<x-wayfinding::stack-opens :back="$this->hasAWayBack()" :title="$this->stack()->name()->shown()" :stacks="$this->stacksToChooseFrom()" :choosing="$this->choosingAStack" />

@if ($this->answer()->went->cameBack())
<x-operator::content>
    <x-design::title>{{ __('stacks.credentials.heading') }}</x-design::title>

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
        </x-design::card>
    @empty
        <x-design::body>{{ __('stacks.credentials.none') }}</x-design::body>
    @endforelse

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

    <x-design::action label="{{ __('health.ask_again') }}" tap="askAgain()" tone="tonal" />
</x-operator::content>
@else
    <x-operator::what-stopped-the-reading
        :settings-would-not-open="$this->theSettingsWouldNotOpen"
        :went="$this->answer()->went"
        :goes="$this->goes()"
    />
@endif

<x-operator::screen-closes :goes="$this->goes()" :here="$this->itsTab()" :marks="$this->marks()" />
