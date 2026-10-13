{{-- Every proof, what it asks and establishes, and what it came to. Not asked
     is never passed, and could not conclude is never failed: each proof says
     which it came to in its own words. --}}
<x-operator::heading>{{ __('plugins.proofs') }}</x-operator::heading>
@forelse ($proofs as $proof)
    <x-operator::entry>
        <x-operator::emphasis>{{ $proof->establishes }}</x-operator::emphasis>
        <x-operator::note>{{ $proof->asks }}</x-operator::note>
        @if ($proof->why !== '')
            <x-design::body>{{ $proof->why }}</x-design::body>
        @endif
        <x-design::body>{{ __($proof->cameToSaid) }}</x-design::body>
        @forelse ($proof->said as $said)
            <x-operator::note>{{ $said }}</x-operator::note>
        @empty
            {{-- Nothing said with it, which is a proof not asked or one that held. --}}
        @endforelse
    </x-operator::entry>
@empty
    <x-operator::note>{{ __('plugins.no_proofs') }}</x-operator::note>
@endforelse
