@use('Modules\Operator\Internal\WhereAskingForNotificationsStands')
<x-design::section :label="__('settings.notifications')">
    <x-design::toggle :label="__('settings.notifications_on')" :on="$on" tap="notificationsTurned()" />
    {{ $slot }}
</x-design::section>

@if ($on)
    <x-design::note>{{ __('settings.notifications_may_be_missing') }}</x-design::note>
@endif

@if ($asking !== null)
    <x-design::card>
        <x-design::body>{{ __($why) }}</x-design::body>

        @if ($asking === WhereAskingForNotificationsStands::Declined)
            <x-design::body>{{ __('settings.notifications_declined') }}</x-design::body>
            <x-design::note>{{ __($without) }}</x-design::note>
            <x-design::action :label="__('settings.notifications_open_settings')" tap="openTheAppsSettings()" />

            @if ($settingsWouldNotOpen)
                <x-design::note>{{ __('settings.notifications_would_not_open') }}</x-design::note>
            @endif
        @else
            <x-design::action :label="__('settings.notifications_allow')" tap="allowNotifications()" />
        @endif

        <x-design::action :label="__('settings.notifications_not_now')" tap="notNow()" tone="tonal" />
    </x-design::card>
@endif
