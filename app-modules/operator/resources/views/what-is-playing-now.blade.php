<x-wayfinding::stack-opens :back="$this->hasAWayBack()" :title="$this->stack()->name()->shown()" :stacks="$this->stacksToChooseFrom()" :choosing="$this->choosingAStack" />

@if ($this->answer()->went->cameBack())
<x-operator::content>
    <x-design::heading>{{ __('stacks.playing.heading') }}</x-design::heading>

    @if (! $this->answer()->isTheOperator)
        <x-design::body>{{ __('stacks.playing.operators_only') }}</x-design::body>
    @elseif (! $this->answer()->heard)
        {{-- Nothing heard yet: only a break makes it something that could
             not be heard. --}}
        <x-design::body>{{ __($this->answer()->broke ? 'stacks.playing.unheard' : 'stacks.playing.not_said_yet') }}</x-design::body>
    @else
        @if ($this->answer()->ago->said !== '')
            {{-- Heard before the stream stopped: what was last true, with when. --}}
            @if ($this->answer()->broke)
                <x-design::body>{{ __('stacks.playing.unheard') }}</x-design::body>
            @endif
            <x-design::note>{{ __('stacks.playing.last_heard', ['ago' => trans_choice($this->answer()->ago->said, $this->answer()->ago->count)]) }}</x-design::note>
        @endif

        @if ($this->answer()->unread !== '')
            <x-design::notice tone="unknown">
                <x-design::strong>{{ __('stacks.playing.unread') }}</x-design::strong>
                <x-design::body>{{ $this->answer()->unread }}</x-design::body>
            </x-design::notice>
        @else
            @forelse ($this->answer()->sessions as $session)
                <x-design::card>
                    <x-design::strong>{{ $session->member }}</x-design::strong>
                    <x-design::body>{{ __($session->what, $session->filling) }}</x-design::body>
                    <x-design::note>{{ __('stacks.playing.on', ['device' => $session->device, 'state' => __($session->state)]) }}</x-design::note>
                </x-design::card>
            @empty
                <x-design::body>{{ __('stacks.playing.nothing') }}</x-design::body>
            @endforelse
        @endif
    @endif

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
