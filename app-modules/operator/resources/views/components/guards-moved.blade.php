{{-- lemonfiber's own guards an act moved onto their plugins, or on a rehearsal
     would: for good, with no yes of their own. One it could not move is still
     on offer, with why. --}}
@forelse ($guards as $guard)
    <x-operator::entry>
        @if ($loop->first)
            <x-design::heading>{{ __('updates.guards.heading') }}</x-design::heading>
            <x-operator::note>{{ __('updates.guards.no_yes') }}</x-operator::note>
        @endif

        <x-design::body>{{ __($guard->said, ['plugin' => $guard->plugin, 'service' => $guard->service]) }}</x-design::body>

        @if ($guard->owesAReason)
            <x-operator::note>{{ $guard->because }}</x-operator::note>
        @endif
    </x-operator::entry>
@empty
@endforelse
