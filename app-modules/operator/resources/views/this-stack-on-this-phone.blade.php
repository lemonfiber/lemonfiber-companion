@use('Modules\Stacks\Api\AStacksScreen')
@use('Modules\Operator\Internal\WhereRemovingTheStackStands')
<x-wayfinding::stack-opens :back="$this->hasAWayBack()" :title="$this->stack()->name()->shown()" :stacks="$this->stacksToChooseFrom()" :choosing="$this->choosingAStack" />

<x-operator::content>
    <x-design::title>{{ __('navigation.menu.stack_settings') }}</x-design::title>

    {{-- Which kinds this stack marks as new: all three until switched off.
         A kind switched off loses its marks at once, and starts again from
         what is current when it is switched on. --}}
    <x-design::section :label="__('news.kinds_heading')">
        @forelse ($this->kindsOfNews() as $kind)
            <x-design::toggle :label="__($kind->said)" :on="$kind->isMarked" :tap="$this->tapping('markAsNew', $kind->kind)" />
        @empty
            {{-- Unreachable while the kinds are three: the list is every kind there is. --}}
        @endforelse
    </x-design::section>
    <x-design::note>{{ __('news.kinds_explained') }}</x-design::note>

    {{-- Whether this stack wakes the phone through a UnifiedPush distributor:
         the operator's alone, on a phone that can be woken this way. Without
         a distributor the switch is drawn and not usable, with where to find
         one, as a link and as a code. --}}
    @if ($this->waking()->drawn)
        <x-design::section :label="__('waking.heading')">
            <x-design::toggle :label="__('waking.switch')" :on="$this->waking()->on" :disabled="! $this->waking()->usable" tap="wakingTurned()" />
        </x-design::section>
        <x-design::note>{{ __($this->waking()->line, ['distributor' => $this->waking()->distributor]) }}</x-design::note>

        @if ($this->choosingADistributor && $this->waking()->choice->offered !== [])
            <x-design::body>{{ __('waking.choose') }}</x-design::body>
            <x-design::chips>
                @forelse ($this->waking()->choice->offered as $choice)
                    <x-design::chip :label="$choice->said" :tap="$this->tapping('wakeThrough', $choice->word)" :chosen="$choice->chosen" />
                @empty
                    {{-- Unreachable: a choice is offered only among two or more. --}}
                @endforelse
            </x-design::chips>
        @endif

        @if ($this->waking()->install !== null)
            <x-design::card>
                <x-design::scannable :rows="$this->waking()->install->squares" :missing="__('waking.no_code')" />
                <x-design::verbatim>{{ $this->waking()->install->link }}</x-design::verbatim>
            </x-design::card>
        @endif

        @if ($this->wakingSaid !== null)
            <x-design::notice tone="trouble">
                @if ($this->wakingSaid->words !== '')
                    <x-design::body>{{ $this->wakingSaid->words }}</x-design::body>
                @else
                    <x-design::body>{{ __($this->wakingSaid->line, $this->wakingSaid->with) }}</x-design::body>
                @endif
            </x-design::notice>
        @endif
    @endif

    <x-design::section>
        <x-design::row :headline="__('served.title')" :goes="$this->goes()->to(AStacksScreen::Served)" />
    </x-design::section>

    <x-design::section>
        <x-design::row :headline="__('settings.remove_from_phone')" tap="askToRemove()" />
    </x-design::section>

    {{-- Asked on this page, where the question can say what goes with the
         stack and that the stack itself keeps running. --}}
    @if ($this->removal === WhereRemovingTheStackStands::Asked)
        <x-design::body>{{ __('settings.remove_confirm', ['name' => $this->stack()->name()->shown()]) }}</x-design::body>
        @if ($this->removingTellsTheStack())
            <x-design::note>{{ __('waking.removing_tells', ['name' => $this->stack()->name()->shown()]) }}</x-design::note>
        @endif
        <x-design::action :label="__('settings.remove')" tap="removeTheStack()" />
        <x-design::action :label="__('settings.keep_it')" tap="keepTheStack()" tone="tonal" />
    @endif

    {{-- The stack could not be told to forget this phone's endpoint: said
         before it goes, since nothing of it is kept to tell it later. --}}
    @if ($this->removal === WhereRemovingTheStackStands::StillHoldsTheAddress)
        <x-design::notice tone="trouble">
            <x-design::body>{{ __('waking.still_held', ['name' => $this->stack()->name()->shown()]) }}</x-design::body>
        </x-design::notice>
        <x-design::action :label="__('settings.remove')" tap="removeAnyway()" />
        <x-design::action :label="__('settings.keep_it')" tap="keepTheStack()" tone="tonal" />
    @endif

    @if ($this->removal === WhereRemovingTheStackStands::Refused)
        <x-design::notice tone="trouble">
            <x-design::body>{{ __('settings.remove_refused', ['name' => $this->stack()->name()->shown()]) }}</x-design::body>
        </x-design::notice>
    @endif
</x-operator::content>

<x-operator::screen-closes :goes="$this->goes()" :here="$this->itsTab()" :marks="$this->marks()" />
