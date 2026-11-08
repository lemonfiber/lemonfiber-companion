@use('Modules\Stacks\Api\AStacksScreen')
<x-wayfinding::stack-opens :back="$this->hasAWayBack()" :title="$this->stack()->name()->shown()" :stacks="$this->stacksToChooseFrom()" :choosing="$this->choosingAStack" />

@if ($this->answer()->went->cameBack())
<x-operator::content>
    <x-design::title>{{ __('restarts.title') }}</x-design::title>

    <x-design::section :label="__('restarts.after_the_last_restart')">
        @if ($this->answer()->nothingKept)
            <x-design::row :headline="__('restarts.nothing_kept')" />
        @else
            <x-design::body>{{ __($this->answer()->startSaid, ['ago' => trans_choice($this->answer()->startedAgo->said, $this->answer()->startedAgo->count)]) }}</x-design::body>

            @if ($this->answer()->declined !== '')
                {{-- The stack's own words for why no check ran. --}}
                <x-design::body>{{ __('restarts.declined', ['reason' => $this->answer()->declined]) }}</x-design::body>
            @endif

            @if ($this->answer()->check->whenSaid !== '')
                <x-operator::a-check-after-a-start :check="$this->answer()->check" />
            @endif

            @if ($this->answer()->failing->starts > 0)
                <x-design::notice tone="trouble">
                    <x-design::strong>{{ trans_choice($this->answer()->failing->said, $this->answer()->failing->starts, ['since' => trans_choice($this->answer()->failing->since->said, $this->answer()->failing->since->count)]) }}</x-design::strong>
                </x-design::notice>
            @endif
        @endif
    </x-design::section>

    <x-design::section :label="__('restarts.keeps_running')">
        @if ($this->runningIsBeingRead())
            {{-- This frame read the last start, so what keeps it running is read on the next one. --}}
            <x-design::row :headline="__('restarts.reading_keeps_running')" />
            <x-design::the-next-frame />
        @elseif ($this->running()->went->cameBack())
            <x-design::body>{{ __($this->running()->keptBySaid) }}</x-design::body>

            @if ($this->running()->instead !== '')
                <x-design::notice tone="unknown">
                    <x-design::body>{{ $this->running()->instead }}</x-design::body>
                </x-design::notice>
            @endif

            @if ($this->running()->missing > 0)
                <x-design::notice tone="trouble">
                    <x-design::strong>{{ trans_choice('stacks.did_not_come_back', $this->running()->missing) }}</x-design::strong>
                </x-design::notice>
            @endif
        @else
            <x-design::row :headline="__('restarts.keeps_running_unread')" />
        @endif

        <x-design::row :headline="__('restarts.keeps_running_open')" :goes="$this->goes()->to(AStacksScreen::Hosting)" />
    </x-design::section>

    <x-design::action :label="__('health.ask_again')" tap="askAgain()" tone="tonal" />
</x-operator::content>
@else
    <x-operator::what-stopped-the-reading
        :settings-would-not-open="$this->theSettingsWouldNotOpen"
        :went="$this->answer()->went"
        :goes="$this->goes()"
    />
@endif

<x-operator::screen-closes :goes="$this->goes()" :here="$this->itsTab()" :marks="$this->marks()" />
