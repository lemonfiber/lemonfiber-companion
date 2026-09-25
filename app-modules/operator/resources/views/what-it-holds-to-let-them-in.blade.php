<x-operator::screen-opens :title="$this->stack()->name()->shown()" />

@if ($this->answer()->went->cameBack())
<x-operator::content>
    <x-operator::emphasis>{{ __('stacks.credentials.heading') }}</x-operator::emphasis>

    @forelse ($this->answer()->held as $credential)
        <x-operator::entry>
            <x-operator::emphasis>{{ $credential->name }}</x-operator::emphasis>
            {{-- Each state in its own words: stale, invalid and rotating ask
                 for three different things and are never one warning. --}}
            <native:text>{{ __($credential->stateSaid) }}</native:text>
            {{-- Who made it decides who fixes it. --}}
            <x-operator::note>{{ __($credential->originSaid) }}</x-operator::note>

            <x-operator::note>{{ __('stacks.credentials.used_by') }}</x-operator::note>
            @forelse ($credential->consumers as $consumer)
                <native:text>{{ $consumer }}</native:text>
            @empty
                {{-- Nothing uses it, which is a reason to remove it rather
                     than a reason to hide it. --}}
                <native:text>{{ __('stacks.credentials.used_by_nothing') }}</native:text>
            @endforelse

            @if ($credential->advisory !== '')
                <x-operator::note>{{ $credential->advisory }}</x-operator::note>
            @endif
        </x-operator::entry>
    @empty
        <x-operator::note>{{ __('stacks.credentials.none') }}</x-operator::note>
    @endforelse

    {{-- What keeping them in files protects against, and what it does not. --}}
    <x-operator::emphasis>{{ __('stacks.credentials.protection.heading') }}</x-operator::emphasis>
    <native:text>{{ $this->answer()->summary }}</native:text>

    <x-operator::note>{{ __('stacks.credentials.protection.against') }}</x-operator::note>
    @forelse ($this->answer()->against as $threat)
        <native:text>{{ $threat }}</native:text>
    @empty
        <native:text>{{ __('stacks.credentials.protection.nothing_listed') }}</native:text>
    @endforelse

    <x-operator::note>{{ __('stacks.credentials.protection.not_against') }}</x-operator::note>
    @forelse ($this->answer()->notAgainst as $threat)
        <native:text>{{ $threat }}</native:text>
    @empty
        <native:text>{{ __('stacks.credentials.protection.nothing_listed') }}</native:text>
    @endforelse

    {{-- Nothing here sets, changes or shows a value. --}}
    <x-operator::note>{{ __('stacks.credentials.at_the_machine') }}</x-operator::note>

    <x-operator::action label="{{ __('health.ask_again') }}" tap="again()" />
</x-operator::content>
@else
    <x-operator::what-stopped-the-reading
        :went="$this->answer()->went"
        :sign-in-goes-to="$this->goes()->signIn()"
    />
@endif

<x-operator::screen-closes :goes="$this->goes()" here="health" />
