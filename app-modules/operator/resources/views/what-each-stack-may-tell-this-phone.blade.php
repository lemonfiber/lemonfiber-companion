<x-operator::screen-opens :title="__('notifications.page.title')" :back="$this->hasAWayBack()" />

<x-operator::content>
    {{-- The phone's own switch first: every section below works within it. --}}
    <x-operator::the-notifications-switch :on="$this->notificationsAreOn()" :asking="$this->askingForNotifications" :settings-would-not-open="$this->theSettingsWouldNotOpen" />

    {{-- One section a stack, in the operator's order. While the phone's
         switch is off every choice is drawn and none is usable, and the
         section says why: a choice is never hidden. --}}
    @forelse ($this->sections() as $section)
        <x-design::section :label="$section->name">
            @if ($section->went->cameBack())
                @forelse ($section->kinds as $kind)
                    <x-design::toggle
                        :label="__($kind->said)"
                        :answers-to="__('notifications.page.switch_for', ['stack' => $section->name, 'kind' => __($kind->said)])"
                        :on="$kind->isOn"
                        :disabled="! $this->notificationsAreOn()"
                        :tap="$this->tapping('kindTurned', $section->stack, $kind->kind)"
                    />
                @empty
                    <x-design::row :headline="__('notifications.page.raises_nothing')" />
                @endforelse
            @else
                {{-- Why the kinds could not be read, in the obstacle's own
                     words; signing in again is the stack's own screen's to offer. --}}
                @if ($section->went->isSignedIn)
                    <x-design::body>{{ __('notifications.page.not_read', ['reason' => __($section->went->met, $section->went->filling())]) }}</x-design::body>
                @else
                    <x-design::body>{{ __('notifications.page.not_read', ['reason' => __('connection.session_has_ended')]) }}</x-design::body>
                @endif

                @if ($section->keptOff !== [])
                    <x-design::body>{{ __('notifications.page.kept_off', ['kinds' => implode(', ', array_map(static fn (string $said): string => __($said), $section->keptOff))]) }}</x-design::body>
                @endif

                <x-design::body>{{ __('notifications.page.kept_floor', ['floor' => __($section->floorSaid)]) }}</x-design::body>
            @endif

            <x-design::link :label="__('alerts.where_alerts_go')" :answers-to="__('alerts.where_alerts_go_for', ['stack' => $section->name])" :goes="$section->alertsGo" />
        </x-design::section>

        {{-- The floor, under Problems, where the stack raises them. --}}
        @if ($section->floor !== null)
            <x-design::row :headline="__('notifications.page.problems_from')" :trailing="__($section->floor->said)" />

            <x-design::chips>
                @forelse ($section->floor->offered as $choice)
                    <x-design::chip
                        :label="__($choice->said)"
                        :answers-to="__('notifications.page.floor_for', ['stack' => $section->name, 'floor' => __($choice->said)])"
                        :tap="$this->tapping('problemsFrom', $section->stack, $choice->word)"
                        :chosen="$choice->chosen"
                        :disabled="! $this->notificationsAreOn() || ! $section->problemsAreOn"
                    />
                @empty
                    {{-- Nothing: there are always four severities. --}}
                @endforelse
            </x-design::chips>

            @if (! $section->problemsAreOn)
                <x-design::note>{{ __('notifications.page.problems_are_off') }}</x-design::note>
            @endif
        @endif

        @if (! $this->notificationsAreOn())
            <x-design::note>{{ __('notifications.page.turn_on_above') }}</x-design::note>
        @endif
    @empty
        @unless ($this->waitsForTheNextFrame())
            <x-design::row :headline="__('notifications.page.no_stacks')" />
        @endunless
    @endforelse

    @if ($this->waitsForTheNextFrame())
        {{-- This frame read one stack, and another is still to be read. --}}
        <x-design::the-next-frame />
    @endif
</x-operator::content>
