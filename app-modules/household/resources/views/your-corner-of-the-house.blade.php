<x-operator::screen-opens :title="__('household.tabs.profile')" :back="$this->hasAWayBack()" />

<x-operator::content>
    <x-design::section>
        <x-design::row :headline="__('household.switch_house')" tap="switchHouse()" />
        <x-design::row :headline="__('household.app_settings')" :goes="$this->appSettings()" :carries="$this->appSettingsSpeakTo()" />
        <x-design::row :headline="__('household.remove_house')" tap="askToRemove()" />
    </x-design::section>

    {{-- The member's own languages, kept on this phone. Every language is
         offered as a chip, the one chosen now marked. --}}
    <x-design::heading>{{ __('household.languages.hear') }}</x-design::heading>
    <x-design::chips>
        @forelse ($this->languages()->hear as $language)
            <x-design::chip label="{{ __($language->said) }}" tap="hearIn('{{ $language->word }}')" :chosen="$language->chosen" />
        @empty
            {{-- Nothing: every language is always offered. --}}
        @endforelse
    </x-design::chips>

    <x-design::heading>{{ __('household.languages.read') }}</x-design::heading>
    <x-design::chips>
        @forelse ($this->languages()->read as $language)
            <x-design::chip label="{{ __($language->said) }}" tap="readIn('{{ $language->word }}')" :chosen="$language->chosen" />
        @empty
            {{-- Nothing: every language, and none, is always offered. --}}
        @endforelse
    </x-design::chips>
    <x-design::note>{{ __('household.languages.kept_on_this_phone') }}</x-design::note>

    {{-- Asked on this screen, where the question can say the member can add
         the house again and that nothing changes at the house. --}}
    @if ($this->confirmingTheRemoval)
        <x-design::body>{{ __('household.remove_house_confirm', ['house' => $this->stack()->name()->shown()]) }}</x-design::body>
        <x-design::action label="{{ __('household.remove') }}" tap="removeTheHouse()" />
        <x-design::action label="{{ __('household.keep_it') }}" tap="keepTheHouse()" tone="tonal" />
    @endif

    @if ($this->removalRefused)
        <x-design::notice tone="trouble">
            <x-design::body>{{ __('household.remove_house_refused', ['house' => $this->stack()->name()->shown()]) }}</x-design::body>
        </x-design::notice>
    @endif
</x-operator::content>

{{-- The houses this phone holds, over the tab while open, by name only. A row
     closes the list and opens that house in the same press; the house the
     member is in only closes it. --}}
<native:bottom-sheet :visible="$this->choosingAHouse" detents="medium" @dismiss="stayInThisHouse()">
    <native:column class="w-full gap-4 p-4">
        @if ($this->choosingAHouse)
            <x-design::title>{{ __('household.your_houses') }}</x-design::title>

            <x-design::section>
                @forelse ($this->housesToChooseFrom() as $house)
                    <x-design::row
                        :headline="$house->name"
                        :icon="$house->icon()"
                        :ios-icon="$house->iosIcon()"
                        :tap="$house->pressed()"
                        :answers-to="$house->current ? __('household.current_house', ['house' => $house->name]) : __('household.open_house', ['house' => $house->name])"
                    />
                @empty
                    {{-- Nothing: this screen is only drawn for a house the phone holds. --}}
                @endforelse

                <x-design::row :headline="__('household.add_house')" :goes="$this->addAHouse()" icon="add" ios-icon="plus" />
            </x-design::section>
        @endif
    </native:column>
</native:bottom-sheet>

<x-household::screen-closes :goes="$this->goes()" :here="$this->itsTab()" />
