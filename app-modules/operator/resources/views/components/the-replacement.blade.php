{{-- Replacing the internal certificate: what it would do, labelled as nothing
     replaced yet, with who a restart would interrupt; or what it came to,
     replaced only where the stack says so, with each holder. --}}
@if ($movedOn !== null)
    <x-design::notice>
        <x-design::strong>{{ __('stacks.credentials.replace.moved') }}</x-design::strong>
        <x-operator::refused-in-its-words :refused="$movedOn" />
    </x-design::notice>
@endif
<x-design::notice :tone="$replacement->tone">
    <x-design::strong>{{ __($replacement->reading ? 'stacks.credentials.replace.reading' : $replacement->settledSaid) }}</x-design::strong>
</x-design::notice>
@if ($replacement->said !== '')
    <x-design::body>{{ $replacement->said }}</x-design::body>
@endif
@if ($replacement->reading && $watching !== null)
    @if (! $watching->read)
        <x-design::note>{{ __('stacks.credentials.replace.watching_unread') }}</x-design::note>
    @elseif ($watching->sessions === [])
        <x-design::note>{{ __('stacks.credentials.replace.nobody_watching') }}</x-design::note>
    @else
        <x-design::note>{{ __('stacks.credentials.replace.watching') }}</x-design::note>
        @forelse ($watching->sessions as $session)
            <x-design::body>{{ __('stacks.credentials.replace.session', $session) }}</x-design::body>
        @empty
            {{-- Unreachable: this list is drawn only where somebody is watching. --}}
        @endforelse
    @endif
@endif
@forelse ($replacement->holders as $holder)
    <x-design::body>{{ __('stacks.credentials.replace.holder', ['holder' => $holder->holder, 'reach' => __($holder->reachSaid)]) }}</x-design::body>
    @if ($holder->detail !== '')
        <x-design::note>{{ $holder->detail }}</x-design::note>
    @endif
@empty
    {{-- No holder was reached: a reading names them in the stack's words above, and an outcome that never served the new certificate reached none. --}}
@endforelse
@if ($replacement->landed && $replacement->inForce !== '')
    <x-design::body>{{ __('stacks.credentials.replace.in_force', ['glance' => $replacement->inForce]) }}</x-design::body>
@endif
<x-design::note>{{ __('stacks.credentials.certificate.not_the_pairing') }}</x-design::note>
@if ($replacement->reading)
    <x-operator::offered-action :label="__('stacks.credentials.replace.agree')" tap="agreeToReplace()" :offer="$offer" />
@elseif (! $replacement->landed)
    <x-operator::offered-action :label="__('stacks.credentials.replace.again')" tap="replaceTheCertificate()" :offer="$offer" tone="tonal" />
@endif
