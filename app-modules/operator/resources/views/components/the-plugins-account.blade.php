{{-- An install's account: the plugin, every change and proof, every setting
     it overrides and capability it contests, and every recipe in full, each
     value it would send elsewhere with its own switch while it can be agreed
     to. After the yes, what the stack's checks made of it, and what went back
     where it did not hold. Drawn the same for an install and for the new
     version an update brings. --}}
    <x-operator::the-plugin :plugin="$install->plugin" />

    <x-operator::heading>{{ __('plugins.changes') }}</x-operator::heading>
    @forelse ($install->changes as $change)
        <x-operator::entry>
            <x-design::body>{{ $change->path }}</x-design::body>
            <x-operator::note>{{ __($change->putsSaid) }}</x-operator::note>
        </x-operator::entry>
    @empty
        <x-operator::note>{{ __('plugins.no_changes') }}</x-operator::note>
    @endforelse

    {{-- Not asked is never passed, and could not conclude is never failed:
         each proof says which it came to in its own words. --}}
    <x-operator::heading>{{ __('plugins.proofs') }}</x-operator::heading>
    @forelse ($install->proofs as $proof)
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

    <x-operator::heading>{{ __('plugins.overrides') }}</x-operator::heading>
    @forelse ($install->overrides as $override)
        <x-operator::entry>
            <x-operator::emphasis>{{ $override->target }}</x-operator::emphasis>
            <x-design::body>{{ $override->because }}</x-design::body>
        </x-operator::entry>
    @empty
        <x-operator::note>{{ __('plugins.no_overrides') }}</x-operator::note>
    @endforelse

    {{-- Choosing between the claimants is the Connections screen's. --}}
    <x-operator::heading>{{ __('plugins.contests') }}</x-operator::heading>
    @forelse ($install->contests as $contest)
        <x-operator::entry>
            <x-operator::emphasis>{{ $contest->capability }}</x-operator::emphasis>
            @if ($contest->by !== '')
                <x-operator::note>{{ __('plugins.asked_by', ['by' => $contest->by]) }}</x-operator::note>
            @endif
            @forelse ($contest->claimants as $claimant)
                <x-design::body>{{ __('plugins.claimant', ['claimant' => $claimant]) }}</x-design::body>
            @empty
                <x-operator::note>{{ __('plugins.no_claimants') }}</x-operator::note>
            @endforelse
        </x-operator::entry>
    @empty
        <x-operator::note>{{ __('plugins.no_contests') }}</x-operator::note>
    @endforelse

    {{-- Every service that would take a privileged shape, with what it is
         granted and given and why, each with its own switch apart from the
         offer: the yes to the install approves none of them. --}}
    <x-operator::heading>{{ __('plugins.shapes') }}</x-operator::heading>
    @forelse ($install->taking as $shape)
        <x-operator::entry>
            <x-operator::emphasis>{{ $shape->service }}</x-operator::emphasis>
            <x-design::body>{{ __($shape->neededFor) }}</x-design::body>
            @forelse ($shape->grants as $grant)
                <x-operator::note>{{ __('plugins.granted', ['grant' => $grant]) }}</x-operator::note>
            @empty
                {{-- Granted no capability beyond every plugin's entry. --}}
            @endforelse
            @forelse ($shape->devices as $device)
                <x-operator::note>{{ __('plugins.given', ['device' => $device]) }}</x-operator::note>
            @empty
                {{-- Given no device beyond every plugin's entry. --}}
            @endforelse
            @if ($shape->approval !== null && $install->agreeable)
                <x-design::toggle :label="__('plugins.approve_shape', ['service' => $shape->service])" :on="$shape->approved" tap="approve({{ $shape->approval }})" />
            @endif
        </x-operator::entry>
    @empty
        <x-operator::note>{{ __('plugins.no_shapes') }}</x-operator::note>
    @endforelse

    {{-- Every recipe as its steps in order, and every value it would carry
         elsewhere as its own line with its own switch. --}}
    <x-operator::heading>{{ __('plugins.recipes') }}</x-operator::heading>
    @forelse ($install->plugin->recipes as $recipe)
        <x-operator::entry>
            <x-operator::emphasis>{{ $recipe->title }}</x-operator::emphasis>
            @if ($recipe->why !== '')
                <x-design::body>{{ $recipe->why }}</x-design::body>
            @endif
            @forelse ($recipe->steps as $step)
                <x-design::body>{{ __('plugins.step', ['method' => $step->method, 'to' => $step->to, 'path' => $step->path]) }}</x-design::body>
                @if ($step->adapter !== '')
                    <x-operator::note>{{ __('plugins.adapter', ['adapter' => $step->adapter]) }}</x-operator::note>
                @endif
            @empty
                <x-operator::note>{{ __('plugins.no_steps') }}</x-operator::note>
            @endforelse
            @forelse ($recipe->pairs as $pair)
                <x-design::body>{{ __('plugins.pair', ['value' => $pair->value, 'to' => $pair->to]) }}</x-design::body>
                @if ($pair->origin !== '')
                    <x-operator::note>{{ __('plugins.origin', ['origin' => $pair->origin]) }}</x-operator::note>
                @endif
                @if ($pair->release !== '')
                    <x-operator::note>{{ __('plugins.release', ['from' => $pair->from, 'why' => $pair->release]) }}</x-operator::note>
                @endif
                @if ($pair->approval !== null && $install->agreeable)
                    <x-design::toggle :label="__('plugins.approve', ['value' => $pair->value, 'to' => $pair->to])" :on="$pair->approved" tap="approve({{ $pair->approval }})" />
                @elseif ($pair->approval === null)
                    <x-operator::note>{{ __('plugins.stays_here') }}</x-operator::note>
                @endif
            @empty
                <x-operator::note>{{ __('plugins.sends_nothing') }}</x-operator::note>
            @endforelse
        </x-operator::entry>
    @empty
        <x-operator::note>{{ __('plugins.no_recipes') }}</x-operator::note>
    @endforelse

    @if (! $install->isAReading)
        {{-- What the stack's own checks made of it, which is half of what makes
             an install one. Unsettled is said and never read as broken. --}}
        @if ($install->checked)
            <x-operator::heading>{{ __('plugins.broke') }}</x-operator::heading>
            @forelse ($install->broke as $check)
                <x-design::body>{{ $check }}</x-design::body>
            @empty
                <x-operator::note>{{ __('plugins.broke_nothing') }}</x-operator::note>
            @endforelse
            <x-operator::heading>{{ __('plugins.unsettled') }}</x-operator::heading>
            @forelse ($install->unsettled as $check)
                <x-design::body>{{ $check }}</x-design::body>
            @empty
                <x-operator::note>{{ __('plugins.all_settled') }}</x-operator::note>
            @endforelse
        @else
            <x-operator::note>{{ __('plugins.not_checked') }}</x-operator::note>
        @endif

        @if ($install->putBack !== null)
            {{-- What went back and what did not, each with its reason. --}}
            <x-operator::heading>{{ __('plugins.what_went_back') }}</x-operator::heading>
            <x-operator::what-going-back-came-to :report="$install->putBack" />
        @endif
    @endif
