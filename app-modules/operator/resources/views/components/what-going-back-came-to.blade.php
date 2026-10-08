@if ($report->rehearsed)
    {{-- A rehearsal, and said to be one before anything else: nothing
         below has happened, and nothing is worded as though it had. --}}
    <x-operator::heading>{{ __('stacks.run_back.a_rehearsal') }}</x-operator::heading>
@endif

{{-- What was left leads, because it is the part worth reading: an operator
     who believes changes went back and finds half of them still standing
     has been told something false. --}}
<x-operator::emphasis>{{ __($report->headline) }}</x-operator::emphasis>

@forelse ($report->left as $left)
    <x-operator::entry>
        <x-operator::emphasis>{{ $left->target }}</x-operator::emphasis>
        <x-design::body>{{ $left->because }}</x-design::body>
    </x-operator::entry>
@empty
    {{-- Nothing was left, which the line above says. --}}
@endforelse

@forelse ($report->noted as $noted)
    @if ($loop->first)
        {{-- It went back and still leaves something behind: a setting that
             re-pointed where data lives goes back while the data stays where
             it was moved. --}}
        <x-operator::emphasis>{{ __('stacks.run_back.noted') }}</x-operator::emphasis>
    @endif

    <x-operator::entry>
        <x-operator::emphasis>{{ $noted->target }}</x-operator::emphasis>
        <x-design::body>{{ $noted->because }}</x-design::body>
    </x-operator::entry>
@empty
    {{-- Nothing about going back needed saying. --}}
@endforelse

<x-operator::emphasis>{{ __($report->reversedSaid) }}</x-operator::emphasis>

@forelse ($report->reversed as $reversed)
    <x-operator::entry>
        <x-design::body>{{ $reversed->target }}</x-design::body>
        <x-operator::note>{{ __($reversed->doesSaid) }}</x-operator::note>
    </x-operator::entry>
@empty
    <x-operator::note>{{ __($report->noneReversed) }}</x-operator::note>
@endforelse
