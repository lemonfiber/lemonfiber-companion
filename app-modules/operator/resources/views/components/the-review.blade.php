{{-- One setting open for changing through the stack's review: what it would come to, agreeing to it, waiting for the downloads, or leaving it. --}}
@if ($proposal !== null)
    {{-- What the stack said this would come to, before
         anything is agreed to. The difference and not just
         the new value: somebody deciding is deciding between
         two things, and a screen showing one is asking them
         to remember the other correctly. --}}
    <x-design::note>
        @if ($proposal->holdsNothingYet)
            {{ __('config.holds_nothing_yet') }}
        @else
            {{ __('config.holds_now', ['value' => $proposal->fromSaid]) }}
        @endif
    </x-design::note>
    {{-- Both sides of the difference, and this is the half
         the input does not already show: what the stack
         understood it to be. An operator agreeing is agreeing
         to this, not to what is in the field. --}}
    <x-design::body>{{ __('config.would_hold', ['value' => $proposal->toSaid]) }}</x-design::body>

    <x-design::note>{{ __($proposal->costSaid) }}</x-design::note>
    <x-design::strong>{{ __($proposal->stanceSaid) }}</x-design::strong>

    <x-design::heading>{{ __('config.findings.heading') }}</x-design::heading>
    @forelse ($proposal->comesTo->lines as $line)
        @if ($line->aside)
            <x-design::note>{{ __($line->said, $line->with) }}</x-design::note>
        @else
            <x-design::body>{{ __($line->said, $line->with) }}</x-design::body>
        @endif
    @empty
        <x-design::note>{{ __('config.findings.nothing') }}</x-design::note>
    @endforelse

    @if ($proposal->wroteSomething)
        {{-- Said only where something was written. A setting
             that already held the value reaches the stance
             above saying so and needs no line about a
             restart that is not going to happen. --}}
        <x-design::note>{{ __('config.services_will_restart') }}</x-design::note>
    @endif

    @if ($proposal->refusalSaid !== '')
        {{-- The stack's own words for why nothing was
             written. Shown as they stand: a reason this app
             reworded would be a second account of something
             it does not understand. --}}
        <x-design::note>{{ $proposal->refusalSaid }}</x-design::note>
    @endif

    {{-- Only a staged change waits on a yes. A blocked one is
         refused again when confirmed, an area the operator
         declared unmanaged among the reasons, so its yes
         would be a control that cannot write. --}}
    @if ($proposal->canBeAgreedTo)
        @if ($proposal->mustBeAgreedFirst)
            {{-- Before the control rather than after it. The
                 core decides which changes cost something,
                 and an operator reading downward should meet
                 the warning before the button rather than
                 underneath it. --}}
            <x-design::notice>
                <x-design::strong>{{ __('config.worth_reading_twice') }}</x-design::strong>
            </x-design::notice>
        @endif

        {{-- The spoken name carries the setting; the drawn
             one stays short. Four cards each offering "Make
             this change" is four controls with one name
             between them, and which card a control is on is
             the one thing somebody being read to cannot
             check. --}}
        @if ($waiting)
            <x-design::note>{{ __('config.landing.waiting') }}</x-design::note>
            <x-operator::following-a-job :said="$landingSays" />
        @elseif ($proposal->comesTo->interrupts)
            <x-operator::note>{{ __('health.coming.interrupts') }}</x-operator::note>
            <x-design::action
                :label="__('health.coming.let_them_finish')"
                :answers-to="__('config.agree_to', ['key' => $key])"
                :tap="$tapping($agreeing ?? 'agree', $key, 'for_the_downloads')"
            />
            <x-design::action
                :label="__('health.coming.go_ahead_now')"
                :answers-to="__('config.agree_to', ['key' => $key])"
                :tap="$tapping($agreeing ?? 'agree', $key, 'go_ahead_now')"
                tone="tonal"
            />
        @else
            <x-design::action
                :label="__('config.agree')"
                :answers-to="__('config.agree_to', ['key' => $key])"
                :tap="$tapping($agreeing ?? 'agree', $key)"
            />
        @endif
    @endif
    @if ($landingSaid !== null)
        <x-design::note>{{ __($landingSaid) }}</x-design::note>
    @endif
@else
    <x-design::action
        :label="__('config.what_would_happen')"
        :answers-to="__('config.what_would_happen_to', ['key' => $key])"
        :tap="$tapping('wouldBe', $key)"
    />
@endif

<x-design::action
    :label="__('config.never_mind')"
    :answers-to="__('config.never_mind_about', ['key' => $key])"
    tap="never()"
    tone="tonal"
/>
