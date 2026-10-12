<x-operator::screen-opens :title="__('household.tabs.search')" :back="$this->hasAWayBack()" />

{{-- The field stays where it is while the titles scroll beneath it, so the
     words searched for are always in sight. --}}
<native:column class="w-full h-full">
<native:column class="w-full gap-2 px-6 pt-4 pb-2">
    <native:outlined-text-input
        native:model="typed"
        label="{{ __('household.search.label') }}"
        placeholder="{{ __('household.search.placeholder') }}"
        autocorrect="off"
        @submit="search()"
    />
    <x-design::action :label="__('household.search.go')" tap="search()" :disabled="! $this->maySearch()" />
</native:column>

@if (! $this->answer()->isSignedIn)
    <native:column class="w-full gap-2 px-6">
        <x-design::body>{{ __('household.signed_out') }}</x-design::body>
        <x-design::action :label="__('connection.sign_in')" :goes="$this->signIn()" />
    </native:column>
@elseif (! $this->answer()->searched)
    {{-- Nothing asked yet, so nothing is said about the house: only what this
         tab is for. --}}
    <native:column class="w-full px-6">
        <x-design::body>{{ __('household.search.explained') }}</x-design::body>
    </native:column>
@else
    <native:column class="w-full gap-2 px-6 pb-2">
        @if ($this->answer()->approval !== '')
            {{-- The core's sentence on whether what they ask for waits, as the
                 core wrote it. --}}
            <x-design::note>{{ $this->answer()->approval }}</x-design::note>
        @endif
        @if ($this->answer()->rows === [] && $this->answer()->stopped === null)
            {{-- Said in as many words: no title under their limits matched,
                 which is an answer and not a blank frame. --}}
            <x-design::body>{{ __('household.search.nothing_found', ['words' => $this->searchedFor]) }}</x-design::body>
        @endif
        @if ($this->answer()->stopped !== null)
            <x-design::notice tone="unknown">
                <x-household::what-stood-in-the-way :met="$this->answer()->stopped->met" :remedy="$this->answer()->stopped->remedy" :filling="$this->answer()->stopped->filling()" :in-the-stacks-words="$this->answer()->stopped->isInTheStacksWords()" />
            </x-design::notice>
            <x-design::action :label="__('household.search.again')" tap="search()" tone="tonal" />
            @if ($this->answer()->stopped->isPutRightInTheAppsSettings())
                <x-design::action :label="__('connection.open_settings')" tap="openTheAppsSettings()" />
                @if ($this->theSettingsWouldNotOpen)
                    <x-design::note>{{ __('connection.settings_would_not_open') }}</x-design::note>
                @endif
            @endif
        @endif
    </native:column>
    {{-- The titles scroll on their own, and the next page is read as the
         member reaches the end of them. --}}
    <native:list class="w-full flex-1" separator on-end-reached="more()">
        @forelse ($this->answer()->rows as $row)
            <x-design::row
                :headline="$row->titled"
                :supporting="__($row->line, ['year' => $row->year, 'kind' => __($row->kind), 'stands' => __($row->stands)])"
                :goes="$this->goes()->found($row->found)"
                :answers-to="__('household.search.open_named', ['title' => $row->titled])"
                :carries="$row->carries"
                :image="$row->pictured"
            />
        @empty
            {{-- Nothing to list: what there is to say is said above. --}}
        @endforelse
    </native:list>
@endif
</native:column>

<x-household::screen-closes :goes="$this->goes()" :here="$this->itsTab()" />
