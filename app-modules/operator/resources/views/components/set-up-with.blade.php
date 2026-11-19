<x-design::section :label="__('settled.section')">
    @if ($settled === null)
        <x-design::row :headline="__('settled.reading')" />
        <x-design::the-next-frame />
    @elseif ($settled->went->cameBack())
        @unless ($settled->finished)
            <x-design::notice tone="attention">
                <x-design::strong>{{ __('settled.unfinished') }}</x-design::strong>
            </x-design::notice>
        @endunless

        <x-design::row :headline="__('settled.data_root')" :supporting="$settled->dataRoot !== '' ? $settled->dataRoot : __('settled.no_data_root')" />
        <x-design::row :headline="__('settled.downloads')" :supporting="__($settled->downloadingSaid)" />
        <x-design::row :headline="__('settled.runs_as')" :supporting="$settled->runsAs" />
        <x-design::note>{{ __($settled->runsAsFromSaid) }}</x-design::note>
        <x-design::note>{{ __('settled.reconfiguring') }}</x-design::note>
    @else
        <x-design::row :headline="__('settled.unread')" />
    @endif
</x-design::section>
