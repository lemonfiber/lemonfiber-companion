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
            <x-design::chip :label="__($choice->said)" :tap="$this->tapping('lockAfterIs', $choice->word)" :chosen="$choice->chosen" />
        @empty
            {{-- Nothing: there are always five choices. --}}
        @endforelse
    </x-design::chips>

    <x-design::note>{{ __('settings.lock_after_is') }}</x-design::note>

    {{-- Whether the operator is told what a stack raises. Off until they turn
         it on; turning it on says why first, and only its button asks the phone. --}}
    @if ($this->offersNotifications())
        <x-design::section :label="__('settings.notifications')">
            <x-design::toggle :label="__('settings.notifications_on')" :on="$this->notificationsAreOn()" :tap="$this->tapping('notificationsTurned')" />
        </x-design::section>

        @if ($this->notificationsAreOn())
            <x-design::note>{{ __('settings.notifications_may_be_missing') }}</x-design::note>
        @endif

        @if ($this->explainingNotifications)
            <x-design::card>
                <x-design::body>{{ __($this->whyNotifications()) }}</x-design::body>

                @if ($this->notificationsDeclined)
                    <x-design::body>{{ __('settings.notifications_declined') }}</x-design::body>
                    <x-design::note>{{ __($this->withoutNotifications()) }}</x-design::note>
                    <x-design::action :label="__('settings.notifications_open_settings')" tap="openTheAppsSettings()" />

                    @if ($this->theSettingsWouldNotOpen)
                        <x-design::note>{{ __('settings.notifications_would_not_open') }}</x-design::note>
                    @endif
                @else
                    <x-design::action :label="__('settings.notifications_allow')" tap="allowNotifications()" />
                @endif

                <x-design::action :label="__('settings.notifications_not_now')" tap="notNow()" tone="tonal" />
            </x-design::card>
        @endif
    @endif

    {{-- How long readings are kept. A count of days is said as that count;
         Other… opens a field for any count the offered ones do not name. --}}
    <x-design::section :label="__($this->speaksTo->kept())">
        <x-design::row :headline="__($this->speaksTo->keepFor())" :trailing="$this->keepReadings()->count === null ? __($this->keepReadings()->said) : trans_choice($this->keepReadings()->said, $this->keepReadings()->count)" />
    </x-design::section>

    <x-design::chips>
        @forelse ($this->keepReadings()->offered as $choice)
            <x-design::chip :label="$choice->count === null ? __($choice->said) : trans_choice($choice->said, $choice->count)" :tap="$this->tapping('keepReadingsFor', $choice->word)" :chosen="$choice->chosen" />
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

            <x-design::action :label="__('settings.save')" tap="saveDays()" />
        </x-design::card>
    @endif

    <x-design::note>{{ __($this->speaksTo->keptExplained()) }}</x-design::note>

    {{-- The order every list of stacks follows. A row is dragged into place,
         or moved one place at a time with a screen reader's actions. --}}
    <x-design::section :label="__($this->speaksTo->theStacks())">
        <x-design::row :headline="__($this->speaksTo->theirOrder())" />
    </x-design::section>

    <x-design::order :rows="$this->stacksInOrder()" change="putStacksInOrder" :label="__($this->speaksTo->theirOrder())" :move-up="'settings.move_up'" :move-down="'settings.move_down'" />

    {{-- Every reading, setting and marker, and never a pairing or a session.
         Asked on this screen rather than in a dialog, where the question can
         say what goes and what stays. --}}
    <x-design::section :label="__('settings.saved_data')">
        <x-design::row :headline="__('settings.clear_saved_data')" tap="askToClear()" />
    </x-design::section>

    @if ($this->confirmingTheClear)
        <x-design::body>{{ __($this->speaksTo->clearing()) }}</x-design::body>
        <x-design::action :label="__('settings.clear')" tap="clearSavedData()" />
        <x-design::action :label="__('settings.keep_it')" tap="keepSavedData()" tone="tonal" />
    @endif
</x-operator::content>
