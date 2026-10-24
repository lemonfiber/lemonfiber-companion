{{-- What ending one grant would do, labelled as nothing ended yet, or what
     it came to; the yes is offered on the reading alone. --}}
<x-design::notice>
    <x-design::strong>{{ __($revoke->wasEnded ? 'stacks.credentials.revoke.ended' : 'stacks.credentials.revoke.reading') }}</x-design::strong>
</x-design::notice>
<x-design::body>{{ __('stacks.credentials.revoke.grant', ['credential' => $revoke->grant->credential, 'whose' => $revoke->grant->whose, 'plugin' => $revoke->grant->plugin, 'fills' => $revoke->grant->fills]) }}</x-design::body>
@if ($revoke->restarts === '')
    <x-design::body>{{ __('stacks.credentials.revoke.restarts_nothing') }}</x-design::body>
@else
    <x-design::body>{{ __('stacks.credentials.revoke.restarts', ['services' => $revoke->restarts]) }}</x-design::body>
@endif
@if ($revoke->unrestarted !== '')
    <x-design::note>{{ __('stacks.credentials.revoke.unrestarted', ['why' => $revoke->unrestarted]) }}</x-design::note>
@endif
<x-design::note>{{ __('stacks.credentials.revoke.stays', ['plugin' => $revoke->grant->plugin, 'fills' => $revoke->grant->fills]) }}</x-design::note>
{{-- Ending a grant takes nothing back that was already handed. --}}
<x-design::note>{{ __('stacks.credentials.revoke.rotate') }}</x-design::note>
@unless ($revoke->wasEnded)
    <x-operator::offered-action :label="__('stacks.credentials.revoke.agree')" tap="agree()" :offer="$offer" />
@endunless
