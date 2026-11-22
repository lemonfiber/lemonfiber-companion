@use('Modules\Stacks\Api\AStacksScreen')
<x-wayfinding::stack-opens :back="$this->hasAWayBack()" :title="$this->stack()->name()->shown()" :stacks="$this->stacksToChooseFrom()" :choosing="$this->choosingAStack" />

@if ($this->answer()->went->cameBack())
<x-operator::content>
    <x-operator::heading>{{ __('stacks.left_alone.heading') }}</x-operator::heading>
    <x-design::body>{{ __('stacks.left_alone.intro') }}</x-design::body>
    <x-design::strong>{{ trans_choice($this->answer()->said, $this->answer()->howMany) }}</x-design::strong>

    @foreach ($this->answer()->areas as $area)
        <x-design::card>
            <x-design::strong>{{ $area->area }}</x-design::strong>
            @if ($area->areaCut)
                <x-design::note>{{ __('stacks.left_alone.area_cut') }}</x-design::note>
            @endif

            <x-design::note>{{ __('stacks.left_alone.your_reason') }}</x-design::note>
            <x-design::body>{{ $area->why }}</x-design::body>
            @if ($area->whyCut)
                <x-design::note>{{ __('stacks.left_alone.why_cut') }}</x-design::note>
            @endif

            @if ($area->coversNothing())
                <x-design::note>{{ __('stacks.left_alone.covers_nothing') }}</x-design::note>
            @else
                <x-design::note>{{ __('stacks.left_alone.covers') }}</x-design::note>
                @foreach ($area->covers as $thing)
                    <x-design::row :headline="$thing->name" :supporting="__($thing->kindSaid)" />
                @endforeach
                @if ($area->coversMore > 0)
                    <x-design::note>{{ trans_choice('stacks.left_alone.covers_more', $area->coversMore) }}</x-design::note>
                @endif
            @endif
        </x-design::card>
    @endforeach

    @if ($this->answer()->more > 0)
        <x-design::note>{{ trans_choice('stacks.left_alone.more', $this->answer()->more) }}</x-design::note>
    @endif

    <x-design::note>{{ __('stacks.left_alone.one_setting') }}</x-design::note>
    <x-design::link :label="__('stacks.left_alone.to_the_settings')" :goes="$this->goes()->to(AStacksScreen::Settings)" />

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
