{{-- A plugin, with what vouches for it. Reviewed or not is always said, and
     a word the stack did not record is left out rather than guessed. --}}
<x-design::card>
    <x-design::strong>{{ __('plugins.named', ['name' => $plugin->name, 'version' => $plugin->version]) }}</x-design::strong>
    @if ($plugin->reviewed)
        <x-design::body>{{ __('plugins.reviewed') }}</x-design::body>
    @else
        <x-design::body>{{ __('plugins.not_reviewed') }}</x-design::body>
    @endif
    @if ($plugin->source !== '')
        <x-operator::note>{{ __('plugins.from', ['source' => $plugin->source]) }}</x-operator::note>
    @endif
    @if ($plugin->standingSaid !== '')
        <x-operator::note>{{ __($plugin->standingSaid, ['why' => $plugin->standingWhy]) }}</x-operator::note>
    @endif
    @forelse ($plugin->outOfContract as $answer)
        @if ($loop->first)
            <x-design::strong>{{ __('plugins.out_of_contract') }}</x-design::strong>
        @endif
        <x-operator::note>{{ __('plugins.answered_out_of_contract', ['capability' => $answer->capability, 'operation' => $answer->operation, 'why' => $answer->why]) }}</x-operator::note>
    @empty
        {{-- Adapters that answered within their contracts are the plugin filling what it was chosen for, which the rest of the card already says. --}}
    @endforelse
    @if ($plugin->revision !== '')
        <x-operator::note>{{ __('plugins.revision', ['revision' => $plugin->revision]) }}</x-operator::note>
    @endif
    @if ($plugin->signed !== '')
        <x-operator::note>{{ __('plugins.signed', ['signed' => $plugin->signed]) }}</x-operator::note>
    @else
        <x-operator::note>{{ __('plugins.unsigned') }}</x-operator::note>
    @endif
    @if ($plugin->upstream !== '')
        <x-operator::note>{{ __('plugins.upstream', ['upstream' => $plugin->upstream]) }}</x-operator::note>
    @endif
    @if ($plugin->licence !== '')
        <x-operator::note>{{ __('plugins.licence', ['licence' => $plugin->licence]) }}</x-operator::note>
    @endif
</x-design::card>
