@use('Modules\Kernel\Api\AskingThemIn')
@use('Modules\Stacks\Api\AStacksScreen')
<x-wayfinding::stack-opens :back="$this->hasAWayBack()" :title="$this->stack()->name()->shown()" :stacks="$this->stacksToChooseFrom()" :choosing="$this->choosingAStack" />

@if ($this->answer()->went->cameBack())
<x-operator::content>
    <x-design::title>{{ __('stacks.invitation.road_in') }}</x-design::title>

    {{-- The whole decision, in the stack's terms: a name, the libraries,
         an age, and what becomes of material with no rating. Nothing about
         how the media server stores any of it. --}}
    <x-design::card>
        <native:outlined-text-input native:model="name" label="{{ __('stacks.invitation.name') }}" supporting="{{ __('stacks.invitation.name_is') }}" />
        <native:outlined-text-input native:model="libraries" label="{{ __('stacks.invitation.libraries') }}" supporting="{{ __('stacks.invitation.libraries_are') }}" />
        <native:outlined-text-input native:model="age" label="{{ __('stacks.invitation.age') }}" supporting="{{ __('stacks.invitation.age_is') }}" />

        {{-- What becomes of unrated material, as chips: the one chosen here is
             marked, and leaving it to the stack is a choice of its own. --}}
        <x-design::note>{{ __('stacks.invitation.unrated_is', ['choice' => __($this->unratedChoice()->said)]) }}</x-design::note>
        <x-design::chips>
            @forelse ($this->unratedChoice()->offered as $choice)
                <x-design::chip label="{{ __($choice->said) }}" tap="unratedIs('{{ $choice->word }}')" :chosen="$choice->chosen" />
            @empty
                {{-- Nothing: every case of the choice is always offered. --}}
            @endforelse
        </x-design::chips>
    </x-design::card>

    @if ($this->howItIsGoing()->notAskable !== '')
        <x-design::notice>
            <x-design::strong>{{ __($this->howItIsGoing()->notAskable) }}</x-design::strong>
        </x-design::notice>
    @endif

    @if ($this->howItIsGoing()->went->cameBack())
        @if ($this->howItIsGoing()->isWorking)
            <x-design::standing
                :said="__('stacks.invitation.working')"
                tone="working"
            />
        @elseif ($this->howItIsGoing()->hasEnded)
            {{-- Not a failure and not a refusal: the stack has no outcome for it
                 any more. Who is in, below, is the stack's answer again. --}}
            <x-design::notice tone="unknown">
                <x-design::strong>{{ __('stacks.invitation.no_outcome', ['name' => $this->howItIsGoing()->askedFor]) }}</x-design::strong>
            </x-design::notice>
            <x-design::action label="{{ __('stacks.invitation.start_again') }}" tap="startAgain()" />
        @elseif ($this->howItIsGoing()->refusal !== '')
            {{-- The stack's answer, with the name it was about, drawn as the
                 reason it is rather than as something to try again. --}}
            <x-design::notice>
                <x-design::strong>{{ __('stacks.invitation.refused', ['name' => $this->howItIsGoing()->askedFor]) }}</x-design::strong>
                <x-design::body>{{ $this->howItIsGoing()->refusal }}</x-design::body>
            </x-design::notice>
            <x-design::action label="{{ __('stacks.invitation.start_again') }}" tap="startAgain()" />
        @elseif ($this->howItIsGoing()->invitation !== null)
            @if ($this->howItIsGoing()->invitation->rehearsed)
                {{-- A rehearsal, said to be one before anything else, and never
                     drawn as an account that exists. --}}
                <x-design::notice>
                    <x-design::strong>{{ __('stacks.invitation.rehearsed', ['name' => $this->howItIsGoing()->invitation->toHand->name]) }}</x-design::strong>
                </x-design::notice>
            @endif
            <x-design::heading>{{ __($this->howItIsGoing()->invitation->standingSaid, ['name' => $this->howItIsGoing()->invitation->toHand->name]) }}</x-design::heading>

            {{-- What it grants, before it is sent. What a limit is and is not
                 goes under the limit it is about. --}}
            <x-design::section :label="__('stacks.invitation.grants')">
                @if ($this->howItIsGoing()->invitation->granted->wroteNothing)
                    <x-design::row :headline="__('stacks.invitation.grants_nothing')" />
                @else
                    @forelse ($this->howItIsGoing()->invitation->granted->libraries as $library)
                        <x-design::row :headline="$library" />
                    @empty
                        <x-design::row :headline="__('stacks.invitation.every_library')" />
                    @endforelse
                    @if ($this->howItIsGoing()->invitation->granted->limit !== '')
                        <x-design::row
                            :headline="__('stacks.invitation.limited_to', ['limit' => $this->howItIsGoing()->invitation->granted->limit])"
                            :supporting="$this->howItIsGoing()->invitation->granted->filtering"
                        />
                    @else
                        <x-design::row :headline="__('stacks.invitation.no_limit')" :supporting="$this->howItIsGoing()->invitation->granted->filtering" />
                    @endif
                    <x-design::row :headline="__('stacks.invitation.unrated_is', ['choice' => __($this->howItIsGoing()->invitation->granted->unratedSaid)])" />
                    <x-design::row :headline="__($this->howItIsGoing()->invitation->granted->requestingSaid)" />
                @endif
            </x-design::section>

            {{-- Whether they can ask yet, which is a second service's answer. --}}
            <x-design::body>{{ __($this->howItIsGoing()->invitation->askingSaid) }}</x-design::body>

            @if ($this->howItIsGoing()->invitation->toHand->lapses)
                {{-- When it lapses, and what lapsing does. --}}
                <x-design::notice>
                    <x-design::strong>{{ trans_choice('stacks.invitation.lapses', $this->howItIsGoing()->invitation->toHand->hours) }}</x-design::strong>
                </x-design::notice>
            @endif

            @if ($this->howItIsGoing()->invitation->mayBeSent)
                <x-operator::offered-action label="{{ __('stacks.invitation.send', ['name' => $this->howItIsGoing()->invitation->toHand->name]) }}" tap="send()" :offer="$this->offered(AskingThemIn::Invite)" />
            @endif

            @if ($this->howItIsGoing()->invitation->toHand->handsOver)
                {{-- The address exactly as the stack sent it, its caution beside
                     it, the same address as a code another phone scans off this
                     screen, and the device's own sharing to pass it on. --}}
                <x-design::heading>{{ __('stacks.invitation.to_hand_over') }}</x-design::heading>
                <x-design::card>
                    <x-design::verbatim>{{ $this->howItIsGoing()->invitation->toHand->url }}</x-design::verbatim>
                    @if ($this->howItIsGoing()->invitation->toHand->caution !== '')
                        <x-design::note>{{ $this->howItIsGoing()->invitation->toHand->caution }}</x-design::note>
                    @endif
                    <x-design::scannable :rows="$this->howItIsGoing()->invitation->toHand->code" missing="{{ __('stacks.invitation.no_code') }}" />
                    @if ($this->howItIsGoing()->invitation->toHand->code !== [])
                        <x-design::note>{{ __('stacks.invitation.code') }}</x-design::note>
                    @endif
                </x-design::card>
                <x-design::action label="{{ __('stacks.invitation.pass_on') }}" tap="passOn()" />
                @if ($this->passedOn !== '')
                    <x-design::note>{{ __($this->passedOn) }}</x-design::note>
                @endif
            @endif

            {{-- Taken back on the way past, with the answer they arrived on. --}}
            <x-design::section :label="__($this->howItIsGoing()->invitation->withdrawnSaid)">
                @forelse ($this->howItIsGoing()->invitation->withdrawn as $withdrawn)
                    <x-design::row :headline="$withdrawn" />
                @empty
                    <x-design::row :headline="__('stacks.invitation.nobody_withdrawn')" />
                @endforelse
            </x-design::section>

            {{-- The other half of what went on the way past: resets that
                 lapsed are switched off rather than removed, so the account
                 and what they watched are still there, and asking them in
                 again switches it back on. Apart from the list above, because
                 one of the two is gone and the other is waiting. --}}
            <x-design::section :label="__($this->howItIsGoing()->invitation->suspendedSaid)">
                @forelse ($this->howItIsGoing()->invitation->suspended as $suspended)
                    <x-design::row :headline="$suspended" />
                @empty
                    <x-design::row :headline="__('stacks.invitation.nobody_switched_off')" />
                @endforelse
            </x-design::section>

            <x-design::action label="{{ __('stacks.invitation.start_again') }}" tap="startAgain()" tone="tonal" />
        @else
            <x-operator::offered-action label="{{ __('stacks.invitation.what_would_it_grant') }}" tap="offer()" :offer="$this->offered(AskingThemIn::Invite)" />
        @endif
    @else
        {{-- Asking, or asking after it, met something: said where the answer
             would have been, with the way back. --}}
        <x-operator::what-stood-in-the-way
            :settings-would-not-open="$this->theSettingsWouldNotOpen"
            :went="$this->howItIsGoing()->went"
            :goes="$this->goes()"
        />
    @endif

    {{-- Who is in already: joined, or an invitation still out. Each card
         offers letting them choose a new password, by picking the person and
         nothing else; no password is shown, set or carried here. --}}
    <x-design::heading>{{ __('stacks.invitation.who_is_in') }}</x-design::heading>
    @forelse ($this->answer()->members as $member)
        <x-design::card>
            <x-design::strong>{{ $member->name }}</x-design::strong>
            <x-design::note>{{ __($member->standingSaid) }}</x-design::note>
            @if ($this->member === $member->name)
                <x-design::body>{{ __('stacks.invitation.taking_it_off_means', ['name' => $member->name]) }}</x-design::body>
                <x-operator::offered-action label="{{ __('stacks.invitation.take_it_off', ['name' => $member->name]) }}" tap="takeThePasswordOff()" :offer="$this->offered(AskingThemIn::TakeThePasswordOff)" />
                <x-design::action
                    label="{{ __('stacks.invitation.never_mind') }}"
                    answers-to="{{ __('stacks.invitation.never_mind_for', ['name' => $member->name]) }}"
                    tap="neverMind()"
                    tone="tonal"
                />
            @else
                <x-operator::offered-action
                    label="{{ __('stacks.invitation.would_take_it_off', ['name' => $member->name]) }}"
                    tap="wouldTakeThePasswordOff('{{ $member->name }}')"
                    :offer="$this->offered(AskingThemIn::TakeThePasswordOff)"
                    drawn="link"
                />
            @endif
            {{-- Connecting a device of theirs is its own screen, where the
                 code is shown only when it is asked for. --}}
            <x-design::link
                label="{{ __('stacks.handoff.title') }}"
                answers-to="{{ __('stacks.handoff.title_for', ['name' => $member->name]) }}"
                :goes="$this->goes()->connecting($member->name)"
            />
            {{-- Taking them out of the household is its own screen, where
                 what it would cost is read before anything is agreed to. --}}
            <x-design::link
                label="{{ __('stacks.removal.would_take_them_out', ['name' => $member->name]) }}"
                :goes="$this->goes()->takingOut($member->name)"
            />
        </x-design::card>
    @empty
        <x-design::body>{{ __('stacks.invitation.nobody_in') }}</x-design::body>
    @endforelse

    {{-- Asking again reads who is in, and asks after work still being
         followed. It is not a retry of anything the stack refused, and is
         named for what it does. --}}
    <x-design::action label="{{ __('stacks.invitation.ask_who_is_in_again') }}" tap="askAgain()" tone="tonal" />
</x-operator::content>
@else
    <x-operator::what-stopped-the-reading
        :settings-would-not-open="$this->theSettingsWouldNotOpen"
        :went="$this->answer()->went"
        :goes="$this->goes()"
    />
@endif

<x-operator::screen-closes :goes="$this->goes()" :here="$this->itsTab()" :marks="$this->marks()" />
