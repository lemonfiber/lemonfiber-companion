<x-wayfinding::stack-opens :back="$this->hasAWayBack()" :title="$this->stack()->name()->shown()" :stacks="$this->stacksToChooseFrom()" :choosing="$this->choosingAStack" />

<x-operator::content>
    <x-design::title>{{ __('navigation.menu.stack_settings') }}</x-design::title>

    {{-- Which kinds this stack marks as new: all three until switched off.
         A kind switched off loses its marks at once, and starts again from
         what is current when it is switched on. --}}
    <x-design::section :label="__('news.kinds_heading')">
        @forelse ($this->kindsOfNews() as $kind)
            <x-design::toggle :label="__($kind->said)" :on="$kind->isMarked" tap="markAsNew('{{ $kind->kind }}')" />
        @empty
            {{-- Unreachable while the kinds are three: the list is every kind there is. --}}
        @endforelse
    </x-design::section>
    <x-design::note>{{ __('news.kinds_explained') }}</x-design::note>

    <x-design::section>
        <x-design::row :headline="__('settings.remove_from_phone')" tap="askToRemove()" />
    </x-design::section>

    {{-- Asked on this page, where the question can say what goes with the
         stack and that the stack itself keeps running. --}}
    @if ($this->confirmingTheRemoval)
        <x-design::body>{{ __('settings.remove_confirm', ['name' => $this->stack()->name()->shown()]) }}</x-design::body>
        <x-design::action label="{{ __('settings.remove') }}" tap="removeTheStack()" />
        <x-design::action label="{{ __('settings.keep_it') }}" tap="keepTheStack()" tone="tonal" />
    @endif

    @if ($this->removalRefused)
        <x-design::notice tone="trouble">
            <x-design::body>{{ __('settings.remove_refused', ['name' => $this->stack()->name()->shown()]) }}</x-design::body>
        </x-design::notice>
    @endif
</x-operator::content>

<x-operator::screen-closes :goes="$this->goes()" :here="$this->itsTab()" :marks="$this->marks()" />
