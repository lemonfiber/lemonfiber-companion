<x-wayfinding::stack-opens :back="$this->hasAWayBack()" :title="$this->stack()->name()->shown()" :stacks="$this->stacksToChooseFrom()" :choosing="$this->choosingAStack" />

@if ($this->answer()->went->cameBack())
<x-operator::content>
    <x-design::title>{{ __('stacks.keys.heading') }}</x-design::title>
    <x-design::note>{{ $this->answer()->purposes }}</x-design::note>
    @if ($this->answer()->refused !== null)
        <x-design::notice>
            <x-design::strong>{{ __('stacks.keys.not_revoked') }}</x-design::strong>
            <x-operator::refused-in-its-words :refused="$this->answer()->refused" />
        </x-design::notice>
    @endif

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

    @if ($this->mintingIsNotOffered() !== '')
        <x-design::note>{{ __($this->mintingIsNotOffered()) }}</x-design::note>
        <x-design::note>{{ __('stacks.keys.made_at_the_terminal') }}</x-design::note>
    @elseif ($this->minted?->claim !== null)
        <x-design::card>
            <x-design::strong>{{ __('stacks.mint.claim_for', ['name' => $this->minted->claim->name]) }}</x-design::strong>
            @if ($this->minutesTheLinkLasts() > 0)
                <x-design::scannable :rows="$this->minted->claim->squares" :missing="__('stacks.mint.no_code')" />
                <x-design::verbatim>{{ $this->minted->claim->link }}</x-design::verbatim>
                <x-design::note>{{ trans_choice('stacks.mint.lapses', $this->minutesTheLinkLasts()) }}</x-design::note>
                <x-design::note>{{ __('stacks.mint.once') }}</x-design::note>
                <x-design::action :label="__('stacks.mint.pass_on')" tap="passOnTheLink()" />
                @if ($this->linkPassedOn !== '')
                    <x-design::note>{{ __($this->linkPassedOn) }}</x-design::note>
                @endif
            @else
                <x-design::body>{{ __('stacks.mint.lapsed') }}</x-design::body>
            @endif
            <x-design::action :label="__('stacks.mint.done')" tap="stopMinting()" tone="tonal" />
        </x-design::card>
    @elseif ($this->minting)
        <x-design::card>
            <x-design::strong>{{ __('stacks.mint.heading') }}</x-design::strong>
            <native:outlined-text-input native:model="mintName" label="{{ __('stacks.mint.name') }}" autocorrect="off" />

            <x-design::body>{{ __('stacks.mint.what_it_may_do') }}</x-design::body>
            <x-design::chips>
                @forelse ($this->whatAKeyMayAdmit() as $scope)
                    <x-design::chip :label="__(sprintf('stacks.mint.admits.%s', $scope))" :tap="$this->tapping('admitting', $scope)" :chosen="$this->mintAdmits === $scope" />
                @empty
                    {{-- What a key may admit is the stack's closed set, and it is never empty. --}}
                @endforelse
            </x-design::chips>
            @if ($this->admitsAMembersOwn())
                <x-design::chips>
                    @forelse ($this->mintMembers as $member)
                        <x-design::chip :label="$member" :tap="$this->tapping('admittingMember', $loop->index)" :chosen="$this->mintMember === $member" />
                    @empty
                        <x-design::note>{{ __('stacks.mint.no_members') }}</x-design::note>
                    @endforelse
                </x-design::chips>
            @endif

            <x-design::body>{{ __('stacks.mint.for') }}</x-design::body>
            <x-design::chips>
                @forelse ($this->whatAKeyMayBeFor() as $purpose)
                    <x-design::chip :label="__(sprintf('stacks.keys.purpose.%s', $purpose))" :tap="$this->tapping('meantFor', $purpose)" :chosen="$this->mintFor === $purpose" />
                @empty
                    {{-- What a key may be for is the stack's closed set, and it is never empty. --}}
                @endforelse
            </x-design::chips>

            @if ($this->needsThePassword())
                <x-design::note>{{ __('stacks.mint.first_from_this_phone') }}</x-design::note>
                <native:outlined-text-input
                    native:model="password"
                    label="{{ __('connection.password_label') }}"
                    content-type="password"
                    keyboard="password"
                    secure
                />
            @else
                <x-design::note>{{ __('stacks.mint.with_face_or_fingerprint') }}</x-design::note>
            @endif

            @if ($this->minted?->said !== null && $this->minted->said !== '')
                <x-design::notice>
                    <x-design::body>{{ __($this->minted->said) }}</x-design::body>
                </x-design::notice>
            @endif
            @if ($this->minted?->refused !== null)
                <x-design::notice>
                    <x-design::strong>{{ __('stacks.mint.no_key_made') }}</x-design::strong>
                    <x-operator::refused-in-its-words :refused="$this->minted->refused" />
                </x-design::notice>
            @endif
            @if ($this->minted?->met !== null)
                <x-design::notice tone="unknown">
                    <x-design::strong>{{ __('stacks.mint.no_key_made') }}</x-design::strong>
                    <x-design::body>{{ __($this->minted->met->met, $this->minted->met->filling()) }}</x-design::body>
                    <x-design::body>{{ __($this->minted->met->remedy, $this->minted->met->filling()) }}</x-design::body>
                </x-design::notice>
            @endif

            <x-design::action :label="__('stacks.mint.make')" :disabled="! $this->mayMint()" tap="mint()" />
            <x-design::action :label="__('stacks.keys.keep_it')" tap="stopMinting()" tone="tonal" />
        </x-design::card>
    @else
        <x-design::action :label="__('stacks.mint.start')" tap="startMinting()" />
    @endif

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
                @if ($key->isThisPhone)
                    <x-design::note>{{ __('stacks.phone_keys.this_phone') }}</x-design::note>
                @endif
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
