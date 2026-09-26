<x-operator::screen-opens :title="$this->stack()->name()->shown()" />

@if ($this->answer()->went->cameBack())
<x-operator::content>
    <x-operator::heading>{{ __('stacks.invitation.road_in') }}</x-operator::heading>

    {{-- The whole decision, in the stack's terms: a name, the libraries,
         an age, and what becomes of material with no rating. Nothing about
         how the media server stores any of it. --}}
    <native:outlined-text-input native:model="name" label="{{ __('stacks.invitation.name') }}" supporting="{{ __('stacks.invitation.name_is') }}" />
    <native:outlined-text-input native:model="libraries" label="{{ __('stacks.invitation.libraries') }}" supporting="{{ __('stacks.invitation.libraries_are') }}" />
    <native:outlined-text-input native:model="age" label="{{ __('stacks.invitation.age') }}" supporting="{{ __('stacks.invitation.age_is') }}" />

    <x-operator::note>{{ __('stacks.invitation.unrated_is', ['choice' => __($this->unratedSaid())]) }}</x-operator::note>
    <x-operator::quiet-action label="{{ __('stacks.invitation.hold_unrated_back') }}" tap="unratedIs('held-back')" />
    <x-operator::quiet-action label="{{ __('stacks.invitation.let_unrated_through') }}" tap="unratedIs('let-through')" />
    <x-operator::quiet-action label="{{ __('stacks.invitation.leave_unrated_to_the_stack') }}" tap="unratedIs('')" />

    @if ($this->howItIsGoing()->notAskable !== '')
        <x-operator::emphasis>{{ __($this->howItIsGoing()->notAskable) }}</x-operator::emphasis>
    @endif

    @if ($this->howItIsGoing()->went->cameBack())
        @if ($this->howItIsGoing()->isWorking)
            <native:text>{{ __('stacks.invitation.working') }}</native:text>
            <x-operator::note>
                {{ __($this->cadence()->saidOnTheScreen(), ['count' => $this->cadence()->seconds()]) }}
            </x-operator::note>
        @elseif ($this->howItIsGoing()->hasEnded)
            {{-- Not a failure and not a refusal: the stack has no outcome for it
                 any more. Who is in, below, is the stack's answer again. --}}
            <native:text>{{ __('stacks.invitation.no_outcome', ['name' => $this->howItIsGoing()->askedFor]) }}</native:text>
            <x-operator::action label="{{ __('stacks.invitation.start_again') }}" tap="startAgain()" />
        @elseif ($this->howItIsGoing()->refusal !== '')
            {{-- The stack's answer, with the name it was about, drawn as the
                 reason it is rather than as something to try again. --}}
            <x-operator::emphasis>{{ __('stacks.invitation.refused', ['name' => $this->howItIsGoing()->askedFor]) }}</x-operator::emphasis>
            <native:text>{{ $this->howItIsGoing()->refusal }}</native:text>
            <x-operator::action label="{{ __('stacks.invitation.start_again') }}" tap="startAgain()" />
        @elseif ($this->howItIsGoing()->invitation !== null)
            @if ($this->howItIsGoing()->invitation->rehearsed)
                {{-- A rehearsal, said to be one before anything else, and never
                     drawn as an account that exists. --}}
                <x-operator::emphasis>{{ __('stacks.invitation.rehearsed', ['name' => $this->howItIsGoing()->invitation->toHand->name]) }}</x-operator::emphasis>
            @endif
            <native:text>{{ __($this->howItIsGoing()->invitation->standingSaid, ['name' => $this->howItIsGoing()->invitation->toHand->name]) }}</native:text>

            {{-- What it grants, before it is sent. --}}
            <x-operator::heading>{{ __('stacks.invitation.grants') }}</x-operator::heading>
            @if ($this->howItIsGoing()->invitation->granted->wroteNothing)
                <native:text>{{ __('stacks.invitation.grants_nothing') }}</native:text>
            @else
                @forelse ($this->howItIsGoing()->invitation->granted->libraries as $library)
                    <native:text>{{ $library }}</native:text>
                @empty
                    <native:text>{{ __('stacks.invitation.every_library') }}</native:text>
                @endforelse
                @if ($this->howItIsGoing()->invitation->granted->limit !== '')
                    <native:text>{{ __('stacks.invitation.limited_to', ['limit' => $this->howItIsGoing()->invitation->granted->limit]) }}</native:text>
                @else
                    <native:text>{{ __('stacks.invitation.no_limit') }}</native:text>
                @endif
                <x-operator::note>{{ $this->howItIsGoing()->invitation->granted->filtering }}</x-operator::note>
                <native:text>{{ __('stacks.invitation.unrated_is', ['choice' => __($this->howItIsGoing()->invitation->granted->unratedSaid)]) }}</native:text>
                <native:text>{{ __($this->howItIsGoing()->invitation->granted->requestingSaid) }}</native:text>
            @endif

            {{-- Whether they can ask yet, which is a second service's answer. --}}
            <native:text>{{ __($this->howItIsGoing()->invitation->askingSaid) }}</native:text>

            @if ($this->howItIsGoing()->invitation->toHand->lapses)
                {{-- When it lapses, and what lapsing does. --}}
                <x-operator::emphasis>{{ trans_choice('stacks.invitation.lapses', $this->howItIsGoing()->invitation->toHand->hours) }}</x-operator::emphasis>
            @endif

            @if ($this->howItIsGoing()->invitation->mayBeSent)
                <x-operator::action label="{{ __('stacks.invitation.send', ['name' => $this->howItIsGoing()->invitation->toHand->name]) }}" tap="send()" />
            @endif

            @if ($this->howItIsGoing()->invitation->toHand->handsOver)
                {{-- The address exactly as the stack sent it, its caution beside
                     it, the same address as a code another phone scans off this
                     screen, and the device's own sharing to pass it on. --}}
                <x-operator::heading>{{ __('stacks.invitation.to_hand_over') }}</x-operator::heading>
                <native:text>{{ $this->howItIsGoing()->invitation->toHand->url }}</native:text>
                @if ($this->howItIsGoing()->invitation->toHand->caution !== '')
                    <x-operator::note>{{ $this->howItIsGoing()->invitation->toHand->caution }}</x-operator::note>
                @endif
                {{-- Dark squares on the accent, the two tokens that hold in light
                     and dark alike, so the code reads the same whichever the
                     phone is set to. A row is never empty; its `@empty` is there
                     because every list here says what nothing looks like. --}}
                @forelse ($this->howItIsGoing()->invitation->toHand->code as $row)
                    <native:row class="bg-theme-accent">
                        @forelse ($row as $dark)
                            @if ($dark)
                                <native:rect :width="4" :height="4" class="bg-theme-on-accent" />
                            @else
                                <native:rect :width="4" :height="4" class="bg-theme-accent" />
                            @endif
                        @empty
                            <x-operator::note>{{ __('stacks.invitation.no_code') }}</x-operator::note>
                        @endforelse
                    </native:row>
                @empty
                    <x-operator::note>{{ __('stacks.invitation.no_code') }}</x-operator::note>
                @endforelse
                @if ($this->howItIsGoing()->invitation->toHand->code !== [])
                    <x-operator::note>{{ __('stacks.invitation.code') }}</x-operator::note>
                @endif
                <x-operator::action label="{{ __('stacks.invitation.pass_on') }}" tap="passOn()" />
                @if ($this->passedOn !== '')
                    <x-operator::note>{{ __($this->passedOn) }}</x-operator::note>
                @endif
            @endif

            {{-- Taken back on the way past, with the answer they arrived on. --}}
            @if ($this->howItIsGoing()->invitation->rehearsed)
                <x-operator::heading>{{ __('stacks.invitation.would_withdraw') }}</x-operator::heading>
            @else
                <x-operator::heading>{{ __('stacks.invitation.withdrew') }}</x-operator::heading>
            @endif
            @forelse ($this->howItIsGoing()->invitation->withdrawn as $withdrawn)
                <native:text>{{ $withdrawn }}</native:text>
            @empty
                <x-operator::note>{{ __('stacks.invitation.nobody_withdrawn') }}</x-operator::note>
            @endforelse

            <x-operator::quiet-action label="{{ __('stacks.invitation.start_again') }}" tap="startAgain()" />
        @else
            <x-operator::action label="{{ __('stacks.invitation.what_would_it_grant') }}" tap="offer()" />
        @endif
    @else
        {{-- Asking, or asking after it, met something: said where the answer
             would have been, with the way back. --}}
        <x-operator::what-stopped-the-reading
            :went="$this->howItIsGoing()->went"
            :sign-in-goes-to="$this->goes()->signIn()"
        />
    @endif

    {{-- Who is in already: joined, or an invitation still out. Each row
         offers letting them choose a new password, by picking the person and
         nothing else; no password is shown, set or carried here. --}}
    <x-operator::heading>{{ __('stacks.invitation.who_is_in') }}</x-operator::heading>
    @forelse ($this->answer()->members as $member)
        <x-operator::entry>
            <x-operator::emphasis>{{ $member->name }}</x-operator::emphasis>
            <x-operator::note>{{ __($member->standingSaid) }}</x-operator::note>
            @if ($this->member === $member->name)
                <native:text>{{ __('stacks.invitation.taking_it_off_means', ['name' => $member->name]) }}</native:text>
                <x-operator::action label="{{ __('stacks.invitation.take_it_off', ['name' => $member->name]) }}" tap="takeThePasswordOff()" />
                <x-operator::quiet-action label="{{ __('stacks.invitation.never_mind') }}" tap="neverMind()" />
            @else
                <x-operator::quiet-action
                    label="{{ __('stacks.invitation.would_take_it_off', ['name' => $member->name]) }}"
                    tap="wouldTakeThePasswordOff('{{ $member->name }}')"
                />
            @endif
        </x-operator::entry>
    @empty
        <x-operator::note>{{ __('stacks.invitation.nobody_in') }}</x-operator::note>
    @endforelse

    {{-- Asking again reads who is in, and asks after work still being
         followed. It is not a retry of anything the stack refused, and is
         named for what it does. --}}
    <x-operator::action label="{{ __('stacks.invitation.ask_who_is_in_again') }}" tap="again()" />
</x-operator::content>
@else
    <x-operator::what-stopped-the-reading
        :went="$this->answer()->went"
        :sign-in-goes-to="$this->goes()->signIn()"
    />
@endif

<x-operator::screen-closes :goes="$this->goes()" here="health" />
