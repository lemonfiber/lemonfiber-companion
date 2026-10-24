{{-- One grant: named by the setting and never by a value, with who it is
     granted to and what for, and ending it where the screen offers that. --}}
<x-design::body>{{ __('stacks.credentials.grants.granted', ['plugin' => $grant->plugin, 'fills' => $grant->fills]) }}</x-design::body>
@if ($grant->approved->said !== '')
    <x-design::note>{{ __('stacks.credentials.grants.approved', ['when' => trans_choice($grant->approved->said, $grant->approved->count)]) }}</x-design::note>
@endif
@if ($offer !== null)
    <x-operator::offered-action
        :label="__('stacks.credentials.grants.revoke')"
        :answers-to="__('stacks.credentials.grants.revoke_it', ['credential' => $grant->credential, 'plugin' => $grant->plugin])"
        :tap="$tapping('revoke', $grant->which)"
        :offer="$offer"
        tone="tonal"
    />
@endif
