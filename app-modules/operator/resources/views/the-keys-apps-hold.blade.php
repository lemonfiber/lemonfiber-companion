<x-wayfinding::stack-opens :back="$this->hasAWayBack()" :title="$this->stack()->name()->shown()" :stacks="$this->stacksToChooseFrom()" :choosing="$this->choosingAStack" />

@if ($this->answer()->went->cameBack())
<x-operator::content>
    <x-design::title>{{ __('stacks.keys.heading') }}</x-design::title>
    <x-design::note>{{ $this->answer()->purposes }}</x-design::note>

    @forelse ($this->answer()->keys as $key)
        <x-design::card>
            <x-design::strong>{{ $key->name }}</x-design::strong>
            <x-design::body>{{ __($key->standsSaid) }}</x-design::body>
            <x-design::body>{{ __($key->admits, $key->admitsFilling) }}</x-design::body>
            <x-design::note>{{ __($key->forSaid) }}</x-design::note>
            @if ($key->byAMember)
                <x-design::note>{{ __('stacks.keys.by_a_member') }}</x-design::note>
            @endif
            @if ($key->minted->said !== '')
                <x-design::note>{{ __('stacks.keys.minted', ['when' => trans_choice($key->minted->said, $key->minted->count)]) }}</x-design::note>
            @endif
            @if ($key->used->said !== '')
                <x-design::note>{{ __('stacks.keys.used', ['when' => trans_choice($key->used->said, $key->used->count)]) }}</x-design::note>
            @else
                <x-design::note>{{ __('stacks.keys.never_used') }}</x-design::note>
            @endif

            @if ($key->mayBeRevoked && $this->revoking === $key->name)
                <x-design::body>{{ __('stacks.keys.revoke_confirm', ['name' => $key->name]) }}</x-design::body>
                <x-design::action label="{{ __('stacks.keys.revoke') }}" :answers-to="__('stacks.keys.revoke_named', ['name' => $key->name])" tap="revoke()" />
                <x-design::action label="{{ __('stacks.keys.keep_it') }}" :answers-to="__('stacks.keys.keep_it_named', ['name' => $key->name])" tap="keepIt()" tone="tonal" />
            @elseif ($key->mayBeRevoked)
                <x-design::action label="{{ __('stacks.keys.revoke') }}" :answers-to="__('stacks.keys.revoke_named', ['name' => $key->name])" tap="askToRevoke({{ $loop->index }})" tone="tonal" />
            @endif
        </x-design::card>
    @empty
        <x-design::body>{{ __('stacks.keys.none') }}</x-design::body>
    @endforelse

    <x-design::note>{{ __('stacks.keys.made_at_the_terminal') }}</x-design::note>

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
