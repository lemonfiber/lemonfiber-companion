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
                <x-design::action :label="__('stacks.keys.revoke')" :answers-to="__('stacks.keys.revoke_named', ['name' => $key->name])" tap="revoke()" />
                <x-design::action :label="__('stacks.keys.keep_it')" :answers-to="__('stacks.keys.keep_it_named', ['name' => $key->name])" tap="keepIt()" tone="tonal" />
            @elseif ($key->mayBeRevoked)
                <x-design::action :label="__('stacks.keys.revoke')" :answers-to="__('stacks.keys.revoke_named', ['name' => $key->name])" :tap="$this->tapping('askToRevoke', $loop->index)" tone="tonal" />
            @endif
        </x-design::card>
    @empty
        <x-design::body>{{ __('stacks.keys.none') }}</x-design::body>
    @endforelse

    <x-design::note>{{ __('stacks.keys.made_at_the_terminal') }}</x-design::note>

    <x-design::title>{{ __('stacks.phone_keys.heading') }}</x-design::title>
    <x-design::note>{{ __('stacks.phone_keys.what_they_are') }}</x-design::note>
    @if ($this->phoneKeysAreBeingRead())
        <x-design::the-next-frame />
    @elseif ($this->phoneKeys()->went->cameBack())
        @if ($this->phoneKeys()->refused !== null)
            <x-design::notice>
                <x-design::strong>{{ __('stacks.phone_keys.not_revoked') }}</x-design::strong>
                <x-operator::refused-in-its-words :refused="$this->phoneKeys()->refused" />
            </x-design::notice>
        @endif

        @forelse ($this->phoneKeys()->keys as $key)
            <x-design::card>
                <x-design::strong>{{ $key->name }}</x-design::strong>
                <x-design::body>{{ __($key->livesSaid) }}</x-design::body>
                @if ($key->registered->said !== '')
                    <x-design::note>{{ __('stacks.phone_keys.registered', ['when' => trans_choice($key->registered->said, $key->registered->count)]) }}</x-design::note>
                @endif
                @if ($key->used->said !== '')
                    <x-design::note>{{ __('stacks.phone_keys.used', ['when' => trans_choice($key->used->said, $key->used->count)]) }}</x-design::note>
                @else
                    <x-design::note>{{ __('stacks.phone_keys.never_used') }}</x-design::note>
                @endif
                @if (! $key->mayBeRevoked)
                    <x-design::body>{{ __('stacks.phone_keys.revoked') }}</x-design::body>
                    @if ($key->revoked->said !== '')
                        <x-design::note>{{ __('stacks.phone_keys.revoked_when', ['when' => trans_choice($key->revoked->said, $key->revoked->count)]) }}</x-design::note>
                    @endif
                @elseif ($this->revokingAPhoneKey === $key->name)
                    <x-design::body>{{ __('stacks.phone_keys.revoke_confirm', ['name' => $key->name]) }}</x-design::body>
                    <x-design::action :label="__('stacks.keys.revoke')" :answers-to="__('stacks.keys.revoke_named', ['name' => $key->name])" tap="revokeThePhoneKey()" />
                    <x-design::action :label="__('stacks.keys.keep_it')" :answers-to="__('stacks.keys.keep_it_named', ['name' => $key->name])" tap="keepThePhoneKey()" tone="tonal" />
                @else
                    <x-design::action :label="__('stacks.keys.revoke')" :answers-to="__('stacks.keys.revoke_named', ['name' => $key->name])" :tap="$this->tapping('askToRevokeAPhoneKey', $loop->index)" tone="tonal" />
                @endif
            </x-design::card>
        @empty
            <x-design::body>{{ __('stacks.phone_keys.none') }}</x-design::body>
        @endforelse
    @else
        <x-design::notice tone="unknown">
            <x-design::strong>{{ __('stacks.phone_keys.unread') }}</x-design::strong>
            @if ($this->phoneKeys()->went->isSignedIn)
                <x-design::body>{{ __($this->phoneKeys()->went->met, $this->phoneKeys()->went->filling()) }}</x-design::body>
                <x-design::body>{{ __($this->phoneKeys()->went->remedy, $this->phoneKeys()->went->filling()) }}</x-design::body>
            @else
                <x-design::body>{{ __('connection.session_has_ended') }}</x-design::body>
            @endif
        </x-design::notice>
    @endif

    <x-design::action :label="__('health.ask_again')" tap="askAgain()" tone="tonal" />
</x-operator::content>
@else
    <x-operator::what-stopped-the-reading
        :settings-would-not-open="$this->theSettingsWouldNotOpen"
        :went="$this->answer()->went"
        :goes="$this->goes()"
    />
@endif

<x-operator::screen-closes :goes="$this->goes()" :here="$this->itsTab()" :marks="$this->marks()" />
