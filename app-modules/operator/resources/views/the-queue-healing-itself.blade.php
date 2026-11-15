<x-wayfinding::stack-opens :back="$this->hasAWayBack()" :title="$this->stack()->name()->shown()" :stacks="$this->stacksToChooseFrom()" :choosing="$this->choosingAStack" />

@if ($this->answer()->went->cameBack())
<x-operator::content>
    <x-design::title>{{ __('queue.title') }}</x-design::title>

    @if ($this->answer()->rehearsed)
        <x-design::notice tone="quiet">
            <x-design::strong>{{ __('queue.rehearsal') }}</x-design::strong>
        </x-design::notice>
    @endif

    <x-design::body>{{ __($this->answer()->healingSaid) }}</x-design::body>
    <x-design::body>{{ trans_choice('queue.strikes_needed', $this->answer()->strikesNeeded) }}</x-design::body>
    <x-design::body>{{ trans_choice($this->answer()->graceSaid, $this->answer()->graceCount) }}</x-design::body>

    @if ($this->answer()->classified->said !== '')
        <x-design::body>{{ __('queue.classified', ['ago' => trans_choice($this->answer()->classified->said, $this->answer()->classified->count)]) }}</x-design::body>
    @else
        <x-design::body>{{ __('queue.never_classified') }}</x-design::body>
    @endif

    @if ($this->answer()->incomplete)
        <x-design::notice tone="unknown">
            <x-design::strong>{{ __('queue.incomplete') }}</x-design::strong>
        </x-design::notice>
    @endif

    @if ($this->answer()->mismatch !== '')
        <x-design::notice tone="attention">
            <x-design::strong>{{ __('queue.mismatch') }}</x-design::strong>
            {{-- The stack's own words for where a client files a curator's downloads. --}}
            <x-design::body>{{ $this->answer()->mismatch }}</x-design::body>
        </x-design::notice>
    @endif

    <x-design::section :label="__('queue.wedged')">
        @forelse ($this->answer()->items as $item)
            <x-design::row :headline="$item->download->name" :supporting="__('queue.held_by', ['service' => $item->download->service])" :trailing="__($item->classSaid)" />

            <x-design::notice tone="attention">
                @if ($item->download->release !== '')
                    <x-design::body>{{ __('queue.release', ['release' => $item->download->release]) }}</x-design::body>
                @endif

                <x-design::strong>{{ __($item->classSaid) }}</x-design::strong>
                {{-- The stack's own words for why it classified the item so. --}}
                <x-design::body>{{ $item->why }}</x-design::body>
                <x-design::body>{{ __('queue.strikes', ['strikes' => $item->strikes, 'needed' => $this->answer()->strikesNeeded]) }}</x-design::body>

                @if ($item->added->said !== '')
                    <x-design::body>{{ __('queue.added', ['ago' => trans_choice($item->added->said, $item->added->count)]) }}</x-design::body>
                @endif

                @if ($item->graceEnds->said !== '')
                    <x-design::body>{{ __('queue.in_grace', ['in' => trans_choice($item->graceEnds->said, $item->graceEnds->count)]) }}</x-design::body>
                @endif

                @forelse ($item->next as $act)
                    @if ($loop->first)
                        <x-design::body>{{ __('queue.next') }}</x-design::body>
                    @endif

                    <x-design::body>{{ __($act) }}</x-design::body>
                @empty
                    <x-design::body>{{ __('queue.next_none') }}</x-design::body>
                @endforelse
            </x-design::notice>
        @empty
            <x-design::row :headline="__('queue.nothing_wedged')" />
        @endforelse
    </x-design::section>

    <x-design::section :label="__('queue.unreachable')">
        @forelse ($this->answer()->unreachable as $service)
            {{-- The stack's own words for why it could not reach the service. --}}
            <x-design::row :headline="$service->service" :supporting="$service->why" />
        @empty
            <x-design::row :headline="__('queue.nothing_unreachable')" />
        @endforelse
    </x-design::section>

    <x-design::section :label="__('queue.runs')">
        @forelse ($this->answer()->runs as $run)
            <x-design::row :headline="__('queue.ran', ['ago' => trans_choice($run->ran->said, $run->ran->count)])" :supporting="$run->rehearsed ? __('queue.rehearsal') : ''" />

            @forelse ($run->items as $acted)
                <x-design::row :headline="$acted->download->name" :supporting="__('queue.held_by', ['service' => $acted->download->service])" />

                @if ($acted->download->release !== '')
                    <x-design::body>{{ __('queue.release', ['release' => $acted->download->release]) }}</x-design::body>
                @endif

                {{-- The stack's own words for why the run acted on it. --}}
                <x-design::body>{{ $acted->why }}</x-design::body>

                @forelse ($acted->acts as $act)
                    <x-design::notice :tone="$act->tone">
                        <x-design::strong>{{ __('queue.act_came_to', ['act' => __($act->actSaid), 'outcome' => __($act->outcomeSaid)]) }}</x-design::strong>

                        @if ($act->insteadSaid !== '')
                            <x-design::body>{{ __('queue.instead', ['act' => __($act->insteadSaid)]) }}</x-design::body>
                        @endif

                        @if ($act->why !== '')
                            {{-- The stack's own words for why the act was refused or fell back. --}}
                            <x-design::body>{{ $act->why }}</x-design::body>
                        @endif
                    </x-design::notice>
                @empty
                    <x-design::body>{{ __('queue.no_acts') }}</x-design::body>
                @endforelse
            @empty
                <x-design::body>{{ __('queue.run_acted_on_nothing') }}</x-design::body>
            @endforelse
        @empty
            <x-design::row :headline="__('queue.no_runs')" />
        @endforelse
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
