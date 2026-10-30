{{-- One secret in the stack's secret store, by its name and never its value. --}}
<x-design::card>
    <x-design::strong>{{ $secret->name }}</x-design::strong>
    @if ($secret->whose !== null)
        <x-design::note>{{ __($secret->whose->key, $secret->whose->filling) }}</x-design::note>
    @endif
    <x-design::body>{{ __($secret->heldSaid) }}</x-design::body>
    <x-design::note>{{ __($secret->fromSaid) }}</x-design::note>
    @forelse ($secret->readers as $reader)
        <x-design::note>{{ __('plugins.settings.read_by', ['service' => $reader->service, 'variable' => $reader->variable]) }}</x-design::note>
    @empty
        <x-design::note>{{ __('plugins.settings.read_by_none') }}</x-design::note>
    @endforelse
</x-design::card>
