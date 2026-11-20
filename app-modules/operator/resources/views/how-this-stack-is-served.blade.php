@use('Modules\Operator\Api\HowTheColumnScrolls')
<x-wayfinding::stack-opens :back="$this->hasAWayBack()" :title="$this->stack()->name()->shown()" :stacks="$this->stacksToChooseFrom()" :choosing="$this->choosingAStack" />

@if ($this->answer()->went->cameBack())
<x-operator::content :scrolls="HowTheColumnScrolls::PulledDownToReadAgain">
    <x-design::section :label="__('served.section')">
        <x-design::row :headline="__('served.named')" :supporting="$this->answer()->named !== '' ? $this->answer()->named : __('served.no_name')" />
        <x-design::row :headline="__('served.port')" :supporting="$this->answer()->port" />
        <x-design::row :headline="__('served.encrypted.heading')" :supporting="__($this->answer()->encrypted)" />
        <x-design::row :headline="__('served.network.heading')" :supporting="__($this->answer()->network)" />
    </x-design::section>

    @if ($this->answer()->unrecorded !== '')
        <x-design::notice tone="attention">
            <x-design::strong>{{ __('served.unrecorded') }}</x-design::strong>
            <x-design::body>{{ $this->answer()->unrecorded }}</x-design::body>
        </x-design::notice>
    @endif

    @if ($this->answer()->certificate !== null)
        <x-design::section :label="__('served.certificate')">
            <x-design::row :headline="__('served.pin.presented')" :supporting="$this->answer()->certificate->atAGlance" />
            <x-design::row :headline="__('served.pin.held')" :supporting="$this->answer()->certificate->pinned" />
            <x-design::note>{{ __($this->answer()->certificate->comparing) }}</x-design::note>
            <x-design::row :headline="__('served.subject')" :supporting="$this->answer()->certificate->subject" />
            @if ($this->answer()->certificate->subjectCut)
                <x-design::note>{{ __('served.cut') }}</x-design::note>
            @endif
            @if ($this->answer()->certificate->issuer !== '')
                <x-design::row :headline="__('served.issuer')" :supporting="$this->answer()->certificate->issuer" />
                @if ($this->answer()->certificate->issuerCut)
                    <x-design::note>{{ __('served.cut') }}</x-design::note>
                @endif
            @else
                <x-design::row :headline="__('served.issuer')" :supporting="__('served.self_signed')" />
            @endif
            <x-design::row :headline="__($this->answer()->certificate->fromSaid)" :supporting="trans_choice($this->answer()->certificate->from->said, $this->answer()->certificate->from->count)" />
            <x-design::row :headline="__($this->answer()->certificate->untilSaid)" :supporting="trans_choice($this->answer()->certificate->until->said, $this->answer()->certificate->until->count)" />
        </x-design::section>

        <x-design::section :label="__('served.names')">
            @forelse ($this->answer()->certificate->names as $name)
                <x-design::row :headline="$name" />
            @empty
                <x-design::row :headline="__('served.no_names')" />
            @endforelse
        </x-design::section>
        @if ($this->answer()->certificate->namesMore > 0)
            <x-design::note>{{ trans_choice('served.names_more', $this->answer()->certificate->namesMore) }}</x-design::note>
        @endif
    @else
        <x-design::note>{{ __('served.plain') }}</x-design::note>
    @endif

    <x-design::note>{{ __('served.replacing') }}</x-design::note>
    <x-design::action :label="__('config.ask_again')" tap="askAgain()" tone="tonal" />
</x-operator::content>
@else
    <x-operator::what-stopped-the-reading
        :settings-would-not-open="$this->theSettingsWouldNotOpen"
        :went="$this->answer()->went"
        :goes="$this->goes()"
    />
@endif

<x-operator::screen-closes :goes="$this->goes()" :here="$this->itsTab()" :marks="$this->marks()" />
