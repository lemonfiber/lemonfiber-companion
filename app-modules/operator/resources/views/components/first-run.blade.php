{{-- N1-R55: which step this is and how many there are, above the step itself,
     because a counter read after the thing it counts is a surprise rather than
     an orientation. `trans_choice` is not used here — both numbers are plural
     in the only sense that matters and neither is ever one word. --}}
<x-operator::note>
    {{ __('onboarding.step', ['step' => $at->step(), 'of' => $at->ofHowMany()]) }}
</x-operator::note>

<x-operator::heading>{{ __($at->said()) }}</x-operator::heading>

<native:text>{{ __($at->explained()) }}</native:text>

{{-- The pairing step draws no controls of its own: the two pairing roads below
     are the same controls an operator with a stack already paired sees, and a
     sequence that ended with its own copy of them would be two spellings of one
     button and one of them untested. --}}
@unless ($at->isThePairing())
    <x-operator::action label="{{ __('onboarding.go_on') }}" tap="{{ $on }}" />

    {{-- N1-R55: leavable, landing on pairing. Quieter than going on, because
         skipping is the thing somebody does when they already know — and a
         second filled button beside the first makes neither of them the way
         forward. --}}
    <x-operator::action label="{{ __('onboarding.skip') }}" tap="{{ $leave }}" />
@endunless
