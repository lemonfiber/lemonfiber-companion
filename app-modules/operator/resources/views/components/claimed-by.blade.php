{{-- Every service that claims a capability, in the order it is handed, each
     with where it came from in the line every service's origin is drawn in. --}}
@forelse ($claimants as $claimant)
    @if ($loop->first)
        <x-design::note>{{ __('stacks.wiring.fills.claimed_by') }}</x-design::note>
    @endif
    <x-design::body>{{ $claimant->name }}</x-design::body>
    @if ($claimant->from !== null)
        <x-operator::came-from :from="$claimant->from" :said="$claimant->from->came->ofAService()" />
    @endif
@empty
    {{-- Nothing claims it, which the line above already says: an unfilled ask
         answers *nothing answers this*, and a link kept by name has no
         claimants to name. --}}
@endforelse
