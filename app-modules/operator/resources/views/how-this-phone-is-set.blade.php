<x-operator::screen-opens :title="__('settings.title')" :back="$this->hasAWayBack()" />

<x-operator::content>
    {{-- First, because it changes what every setting below can do: with nowhere
         safe to keep anything, nothing chosen here outlasts the launch. --}}
    @if ($this->keepsNothing())
        <x-design::notice>
            <x-design::body>{{ __('settings.no_secure_storage') }}</x-design::body>
        </x-design::notice>
    @endif

    {{-- How long the app may be away before the lock asks again. The row says
         what is in force; the chips below it offer every choice. --}}
    <x-design::section :label="__('settings.lock')">
        <x-design::row :headline="__('settings.lock_after')" :trailing="__($this->lockAfter()->said)" />
    </x-design::section>

    <x-design::chips>
        @forelse ($this->lockAfter()->offered as $choice)
            <x-design::chip label="{{ __($choice->said) }}" tap="lockAfterIs('{{ $choice->word }}')" :chosen="$choice->chosen" />
        @empty
            {{-- Nothing: there are always five choices. --}}
        @endforelse
    </x-design::chips>

    <x-design::note>{{ __('settings.lock_after_is') }}</x-design::note>

    {{-- How long readings are kept. A count of days is said as that count;
         Other… opens a field for any count the offered ones do not name. --}}
    <x-design::section :label="__('settings.readings')">
        <x-design::row :headline="__('settings.keep_readings')" :trailing="trans_choice($this->keepReadings()->said, $this->keepReadings()->count)" />
    </x-design::section>

    <x-design::chips>
        @forelse ($this->keepReadings()->offered as $choice)
            <x-design::chip label="{{ trans_choice($choice->said, $choice->count) }}" tap="keepReadingsFor('{{ $choice->word }}')" :chosen="$choice->chosen" />
        @empty
            {{-- Nothing: there are always six choices. --}}
        @endforelse
    </x-design::chips>

    @if ($this->typingDays)
        <x-design::card>
            <native:outlined-text-input
                native:model="days"
                label="{{ __('settings.days_label') }}"
                supporting="{{ __('settings.days_between', $this->daysAllowed()) }}"
                keyboard="number"
                :error="$this->daysRefused"
            />

            <x-design::action label="{{ __('settings.save') }}" tap="saveDays()" />
        </x-design::card>
    @endif

    <x-design::note>{{ __('settings.keep_readings_is') }}</x-design::note>

    {{-- The order every list of stacks follows. A row is dragged into place,
         or moved one place at a time with a screen reader's actions. --}}
    <x-design::section :label="__('settings.stacks')">
        <x-design::row :headline="__('settings.stack_order')" />
    </x-design::section>

    <x-design::order :rows="$this->stacksInOrder()" change="putStacksInOrder" :label="__('settings.stack_order')" :move-up="'settings.move_up'" :move-down="'settings.move_down'" />

    {{-- Every reading, setting and marker, and never a pairing or a session.
         Asked on this screen rather than in a dialog, where the question can
         say what goes and what stays. --}}
    <x-design::section :label="__('settings.saved_data')">
        <x-design::row :headline="__('settings.clear_saved_data')" tap="askToClear()" />
    </x-design::section>

    @if ($this->confirmingTheClear)
        <x-design::body>{{ __('settings.clear_confirm') }}</x-design::body>
        <x-design::action label="{{ __('settings.clear') }}" tap="clearSavedData()" />
        <x-design::action label="{{ __('settings.keep_it') }}" tap="keepSavedData()" tone="tonal" />
    @endif
</x-operator::content>
