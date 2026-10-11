<x-wayfinding::stack-opens :back="$this->hasAWayBack()" :title="$this->stack()->name()->shown()" :stacks="$this->stacksToChooseFrom()" :choosing="$this->choosingAStack" />

@if ($this->shown()->went->cameBack())
<x-operator::content>
    <x-design::heading>{{ __('stacks.playing.heading') }}</x-design::heading>

    @if (! $this->shown()->isTheOperator)
        <x-design::body>{{ __('stacks.playing.operators_only') }}</x-design::body>
    @elseif (! $this->shown()->heard)
        {{-- Nothing heard yet: only a break makes it something that could
             not be heard. --}}
        <x-design::body>{{ __($this->shown()->broke ? 'stacks.playing.unheard' : 'stacks.playing.not_said_yet') }}</x-design::body>
    @else
        @if ($this->shown()->ago->said !== '')
            {{-- Heard before the stream stopped: what was last true, with when. --}}
            @if ($this->shown()->broke)
                <x-design::body>{{ __('stacks.playing.unheard') }}</x-design::body>
            @endif
            <x-design::note>{{ __('stacks.playing.last_heard', ['ago' => trans_choice($this->shown()->ago->said, $this->shown()->ago->count)]) }}</x-design::note>
        @endif

        @if ($this->shown()->unread !== '')
            <x-design::notice tone="unknown">
                <x-design::strong>{{ __('stacks.playing.unread') }}</x-design::strong>
                <x-design::body>{{ $this->shown()->unread }}</x-design::body>
            </x-design::notice>
        @else
            @forelse ($this->shown()->sessions as $session)
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

    <x-design::action label="{{ __('health.ask_again') }}" tap="askAgain()" tone="tonal" />
</x-operator::content>
@else
    <x-operator::what-stopped-the-reading
        :settings-would-not-open="$this->theSettingsWouldNotOpen"
        :went="$this->shown()->went"
        :goes="$this->goes()"
    />
@endif

<x-operator::screen-closes :goes="$this->goes()" :here="$this->itsTab()" :marks="$this->marks()" />
