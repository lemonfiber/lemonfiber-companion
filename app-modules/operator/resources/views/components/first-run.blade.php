{{-- Which step this is and how many there are, above the step itself,
     because a counter read after the thing it counts is a surprise rather than
     an orientation. `trans_choice` is not used here — both numbers are plural
     in the only sense that matters and neither is ever one word. --}}
<x-design::note>{{ __('onboarding.step', ['step' => $at->step(), 'of' => $at->ofHowMany()]) }}</x-design::note>

<x-design::title>{{ __($at->said()) }}</x-design::title>

<x-design::body>{{ __($at->explained()) }}</x-design::body>

{{-- The pairing step draws no control of its own: the pairing road below is
     the same control an operator with a stack already paired sees, and a
     sequence that ended with its own copy of it would be two spellings of one
     button with one of them untested. --}}
@unless ($at->isThePairing())
    <x-design::action label="{{ __('onboarding.go_on') }}" tap="{{ $on }}" />

    {{-- Leavable, landing one step short of pairing. Tonal, because skipping
         is the thing somebody does when they already know, and going on is
         the way forward. Not offered from that step, where it would land
         where it already is. --}}
    @if ($at->mayBeSkipped())
        <x-design::action label="{{ __('onboarding.skip') }}" tap="{{ $leave }}" tone="tonal" />
    @endif
@endunless
