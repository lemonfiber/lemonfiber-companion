{{-- Each starting file whose placeholders an update changes: what it was
     seeded from against what the new version would seed it from, by the
     secret's id and never a value. The file on the machine stays as it is. --}}
@forelse ($files as $file)
    <x-operator::entry>
        @if ($loop->first)
            <x-operator::heading>{{ __('plugins.reshipped.heading') }}</x-operator::heading>
            <x-operator::note>{{ __('plugins.reshipped.left_as_it_is') }}</x-operator::note>
        @endif
        <x-design::body>{{ $file->path }}</x-design::body>
        <x-operator::note>{{ __('plugins.reshipped.was') }}</x-operator::note>
        @forelse ($file->was as $placeholder)
            <x-operator::note>{{ __('plugins.placeholder', ['secret' => $placeholder->secret, 'form' => __($placeholder->formSaid)]) }}</x-operator::note>
        @empty
            <x-operator::note>{{ __('plugins.reshipped.no_placeholders') }}</x-operator::note>
        @endforelse
        <x-operator::note>{{ __('plugins.reshipped.becomes') }}</x-operator::note>
        @forelse ($file->becomes as $placeholder)
            <x-operator::note>{{ __('plugins.placeholder', ['secret' => $placeholder->secret, 'form' => __($placeholder->formSaid)]) }}</x-operator::note>
        @empty
            <x-operator::note>{{ __('plugins.reshipped.no_placeholders') }}</x-operator::note>
        @endforelse
    </x-operator::entry>
@empty
    {{-- The new version changes no starting file's placeholders, which is the ordinary case. --}}
@endforelse
