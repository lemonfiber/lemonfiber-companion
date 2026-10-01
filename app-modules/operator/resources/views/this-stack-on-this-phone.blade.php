<x-operator::stack-opens :title="$this->stack()->name()->shown()" :stacks="$this->stacksToChooseFrom()" :choosing="$this->choosingAStack" />

<x-operator::content>
    <x-design::title>{{ __('navigation.menu.stack_settings') }}</x-design::title>

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

<x-operator::screen-closes :goes="$this->goes()" :here="$this->itsTab()" />
