@if ($came?->stopped !== null)
    <x-design::notice tone="quiet">
        <x-design::strong>{{ $came->channel }}</x-design::strong>
        <x-household::what-stood-in-the-way :stopped="$came->stopped" />
    </x-design::notice>
@elseif ($came !== null && $came->inTheHousesWords !== '')
    <x-design::note>{{ $came->channel }}: {{ $came->inTheHousesWords }}</x-design::note>
@elseif ($came !== null)
    <x-design::note>{{ $came->channel }}: {{ __($came->key) }}</x-design::note>
@endif
